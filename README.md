# CRM Stages — Прототип логики стадий CRM

Компонент для Joomla 4, реализующий логику стадий воронки продаж. Менеджер не может «перепрыгнуть» вперёд без выполнения обязательных действий на текущей стадии.

## Быстрый старт

```bash
# Сборка и запуск тестов (PHP и Composer не нужны локально)
docker build -t crm-stages-test -f docker/Dockerfile .
docker run --rm crm-stages-test vendor/bin/phpunit

# Запуск с MariaDB (для полного окружения)
docker-compose up -d
```

## Архитектура

Реализация в виде **компонента Joomla 4** (`com_crmstages`). Компонент выбран как основная единица функциональности Joomla — поддерживает собственные таблицы, MVC, маршрутизацию. Модуль или плагин слишком ограничены для полноценной CRM-логики.

Ключевое решение: **вся бизнес-логика вынесена в чистые PHP-классы** (Domain Layer), не зависящие от Joomla Framework. Это позволяет тестировать логику изолированно и переносить на другой фреймворк при необходимости.

```
src/
├── Controller/          # API Layer — HTTP → JSON
│   ├── CompanyController.php
│   ├── EventController.php
│   └── ActionController.php
├── Service/             # Service Layer — оркестрация
│   ├── CompanyService.php
│   ├── EventService.php
│   └── ActionService.php
├── Domain/              # Domain Layer — чистая бизнес-логика
│   ├── StageEngine.php
│   ├── TransitionValidator.php
│   ├── StageMap.php
│   ├── StageInfo.php
│   └── ActionRestrictions.php
├── DTO/                 # Data Transfer Objects
│   ├── CompanyDTO.php
│   ├── CompanyCardDTO.php
│   ├── Event.php
│   ├── TransitionResult.php
│   └── ValidationResult.php
└── Repository/          # Data Layer — in-memory (мок для БД)
    ├── CompanyRepository.php
    └── EventRepository.php
```

### Слои

| Слой | Ответственность | Зависимости |
|------|----------------|-------------|
| API Layer | HTTP-запросы → JSON-ответы, обработка ошибок | Service Layer |
| Service Layer | Оркестрация: координация домена и репозиториев | Domain + Repository |
| Domain Layer | Бизнес-логика стадий, валидация, ограничения | Нет (чистый PHP) |
| Data Layer | Хранение данных (in-memory, заменяется на Joomla DB API) | DTO |

## Стадии воронки

| Код | MLS | Название | Условие выхода | Ограничения |
|-----|-----|----------|---------------|-------------|
| Ice | C0 | Ice | Разговор с ЛПР | Нельзя: счёт, КП, демо |
| Touched | C1 | Touched | Заполнена форма дискавери | Нельзя: счёт, КП, демо |
| Aware | C2 | Aware | Запланировано демо | Нельзя: счёт, КП |
| Interested | W1 | Interested | Демо с датой | Нельзя: счёт, КП |
| demo_planned | W2 | Demo Planned | Проведено демо | Нельзя: счёт, КП |
| Demo_done | W3 | Demo Done | Счёт или КП (демо < 60 дней) | Всё доступно |
| Committed | H1 | Committed | Оплата получена | Всё доступно |
| Customer | H2 | Customer | Удостоверение выдано | Всё доступно |
| Activated | A1 | Activated | Терминальная | — |
| Null | N0 | Отказ | Терминальная | Всё запрещено |

## Модель данных

6 таблиц с индексами (см. `docker/init.sql`):

- **crmstages_companies** — компании с текущей стадией, optimistic locking через `stage_code`
- **crmstages_events** — журнал событий (append-only), составные индексы `(company_id, event_type)` и `(company_id, created_at DESC)`
- **crmstages_discovery** — форма дискавери (1:1 с компанией)
- **crmstages_demos** — демонстрации с датами планирования и проведения
- **crmstages_invoices** — счета со статусами (created → sent → paid)
- **crmstages_certificates** — удостоверения

### Масштабирование до ~10k компаний/день

1. **Индексация**: составные индексы на часто используемые комбинации, покрывающие индексы для основных запросов
2. **Event Sourcing lite**: таблица событий — append-only, минимум блокировок при записи. Текущее состояние — materialized view в `companies`
3. **Optimistic locking**: `UPDATE companies SET stage_code = ? WHERE id = ? AND stage_code = ?` — без блокировок при чтении
4. **Партиционирование**: при росте — партиционирование events по `created_at` (месячные партиции)
5. **Кэширование**: Redis/Memcached для карточек компаний, инвалидация при записи события

## API Endpoints

| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/companies/{id}` | Карточка компании |
| POST | `/api/companies/{id}/transition` | Переход на следующую стадию |
| POST | `/api/companies/{id}/transition-null` | Переход в Null (отказ) |
| POST | `/api/companies/{id}/actions/{action}` | Выполнить действие |
| GET | `/api/companies/{id}/events` | Журнал событий |
| GET | `/api/companies/{id}/events?type={type}` | Фильтр событий по типу |

### Формат ошибок

```json
{
    "success": false,
    "errors": [
        {
            "code": "TRANSITION_CONDITIONS_NOT_MET",
            "message": "Требуется разговор с ЛПР (lpr_conversation)"
        }
    ]
}
```

Коды: `TRANSITION_CONDITIONS_NOT_MET`, `ACTION_RESTRICTED`, `COMPANY_NOT_FOUND`, `STAGE_CONFLICT`, `NULL_STAGE_TERMINAL`, `INVALID_ACTION`.

## Тестирование

### Двойной подход

Проект использует **два уровня тестирования**:

1. **Property-based тесты** (Eris/PHPUnit) — проверяют универсальные свойства системы на случайных данных (100+ итераций каждый)
2. **Unit-тесты** — проверяют конкретные сценарии и edge cases для каждого перехода

### Property-based тесты (10 свойств)

| # | Свойство | Что проверяет |
|---|----------|---------------|
| 1 | Transition validity | Переход успешен ⟺ условия выхода выполнены |
| 2 | Sequential transitions | Нельзя перепрыгнуть стадию |
| 3 | Action restrictions | Ограничения действий по стадиям |
| 4 | Demo freshness | Правило 60 дней для Demo_done |
| 5 | Null stage behavior | Null достижим из любой стадии, терминален |
| 6 | Event completeness | Все события содержат обязательные поля |
| 7 | Event ordering | Обратная хронологическая сортировка |
| 8 | Event filtering | Фильтрация по типу возвращает только нужный тип |
| 9 | Company round-trip | Сериализация/десериализация CompanyDTO |
| 10 | Event round-trip | Сериализация/десериализация Event |

### Unit-тесты (40 тестов)

- **EarlyStageTransitionsTest**: Ice→Touched, Touched→Aware, Aware→Interested, Interested→demo_planned (позитивные + негативные)
- **MidStageTransitionsTest**: demo_planned→Demo_done, Demo_done→Committed (включая 59/60/61 день), Committed→Customer
- **LateStageTransitionsTest**: Customer→Activated, терминальность Activated и Null, Null из каждой стадии, запрет перепрыгивания

### Запуск тестов

```bash
# Все тесты
docker run --rm crm-stages-test vendor/bin/phpunit

# Только unit-тесты
docker run --rm crm-stages-test vendor/bin/phpunit --testsuite=Unit

# Только property-based тесты
docker run --rm crm-stages-test vendor/bin/phpunit --testsuite=Property
```

### Лог прогона

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.30
Configuration: /app/phpunit.xml

.........................................................         57 / 57 (100%)

Time: 00:00.104, Memory: 12.00 MB

OK (57 tests, 4799 assertions)
```

### Цикл «нашли баг → исправили → тесты зелёные»

При разработке property-based тест #4 (Demo freshness) выявил проблему: метод `validateDemoDone()` использовал `new \DateTimeImmutable()` внутри, что делало тест недетерминированным. Решение: добавлен метод `validateDemoDoneAt(\DateTimeImmutable $now)` для тестируемости, основной метод делегирует в него с текущим временем. После исправления — все тесты зелёные.

## AI-workflow

### Инструменты

- **Kiro** (AI IDE) — основной инструмент разработки, использовался на всех этапах

### Процесс

1. **Спецификация** (Kiro Specs): ТЗ → requirements.md (14 требований в формате EARS) → design.md (архитектура, модель данных, 10 correctness properties) → tasks.md (11 эпиков с трассировкой к требованиям)
2. **Инкрементальная реализация**: каждая задача строится на предыдущей, чекпоинты после каждого слоя
3. **Property-based тестирование**: AI сгенерировал генераторы случайных данных и 10 свойств, покрывающих все acceptance criteria
4. **Контроль качества**: Docker-контейнер для изолированного запуска тестов, все тесты проходят перед переходом к следующей задаче

### Контроль качества и рисков

- **Галлюцинации**: каждый блок кода проверяется запуском тестов в Docker (не доверяем — проверяем)
- **Безопасность**: optimistic locking, валидация входных данных, типизированные DTO
- **Лицензии**: используются только MIT/BSD-лицензированные зависимости (PHPUnit, Eris)
- **Трассировка**: каждый тест аннотирован ссылками на требования

### Где AI дал выигрыш

- **Спецификация**: формализация ТЗ в EARS-формат за минуты вместо часов
- **Архитектура**: вывод correctness properties из acceptance criteria
- **Код**: генерация boilerplate (DTO, Repository, Controller) с правильной типизацией
- **Тесты**: property-based тесты с генераторами — сложно писать вручную, AI справился хорошо
- **Рефакторинг**: выделение `validateDemoDoneAt()` для тестируемости по результатам PBT

## Что бы улучшил

- **Фронтенд**: SPA на Vue.js/Alpine.js с карточкой компании, drag-and-drop по стадиям
- **Интеграция с Joomla**: замена in-memory репозиториев на Joomla Database API (`DatabaseInterface`)
- **Авторизация**: ACL через Joomla Access, разграничение по менеджерам
- **Интеграции**: webhook-уведомления при переходах, интеграция с телефонией для автоматической фиксации звонков
- **Мониторинг**: метрики конверсии по стадиям, среднее время на стадии, bottleneck-анализ
- **CI/CD**: GitHub Actions с автоматическим запуском тестов на каждый PR

## Фронтенд

Next.js 14 SPA с `output: 'export'` — собирается в статический HTML/CSS/JS, который можно залить на любой shared-хостинг без Node.js.

```bash
cd frontend
npm install
npm run build    # → frontend/out/ — готовая статика
npm run dev      # → http://localhost:3000 — для разработки
```

Стек: Next.js 14, React 18, TypeScript, Tailwind CSS, Framer Motion, Lucide React.

Фронтенд полностью зеркалит бэкенд-логику: стадии, ограничения действий, условия переходов. Карточка компании включает:
- прогресс-бар стадий с тултипами условий выхода,
- инструкцию менеджера с кнопкой копирования,
- доступные и заблокированные действия (shake-анимация при клике на заблокированное),
- историю событий с фильтрацией по типу,
- переход на следующую стадию и в Null (с подтверждением),
- toast-уведомления.

В проде мок-данные заменяются на вызовы REST API бэкенда.

## Технологии

- PHP 8.2, Joomla 4 (архитектура компонента)
- PHPUnit 10.5, Eris 1.x (property-based testing)
- Next.js 14, React 18, TypeScript, Tailwind CSS
- Docker, MariaDB 10.6
- PSR-4 autoloading, strict types
