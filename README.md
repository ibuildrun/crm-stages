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
| Aware | C2 | Aware | Запланировано демо | Нельзя: счёт, КП, показ демо |
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

| Инструмент | Роль | Где применялся |
|------------|------|----------------|
| **Kiro** (AI IDE) | Основная среда разработки | Весь проект от спецификации до деплоя |
| **Kiro Specs** | Структурированная спецификация | requirements.md → design.md → tasks.md |
| **Kiro Steering** | Контекстные правила для AI | product.md, structure.md, tech.md — автоматически подгружаются в каждый запрос |
| **Kiro Chat (Autopilot)** | Генерация и рефакторинг кода | Реализация слоёв, тестов, фронтенда |

### Процесс: как строилась работа

#### Этап 1: Спецификация (Kiro Specs)

ТЗ было загружено в Kiro Specs, который разбил его на три документа:

- **requirements.md** — 14 требований в формате EARS (Easy Approach to Requirements Syntax), каждое с acceptance criteria в формате Given/When/Then
- **design.md** — архитектура (слои, зависимости), модель данных (6 таблиц), API-контракты, 10 correctness properties для property-based тестов
- **tasks.md** — 11 эпиков с чекбоксами, трассировкой к требованиям и порядком выполнения

Пример промпта на этом этапе: загрузка полного текста ТЗ с просьбой «Разбей на requirements в формате EARS, затем design с моделью данных и correctness properties, затем tasks с трассировкой».

Выигрыш: структурированная спецификация за ~15 минут вместо ~2-3 часов ручной работы. Specs обеспечили трассировку требований через весь проект.

#### Этап 2: Steering-файлы (контекст для AI)

Созданы три steering-файла в `.kiro/steering/`:

- **product.md** — бизнес-правила, доменный язык (ЛПР, дискавери, КП), MLS-коды
- **structure.md** — архитектура слоёв, ключевые паттерны, структура проекта
- **tech.md** — стек, команды сборки/тестирования, инфраструктура

Эти файлы автоматически подгружаются в каждый запрос к AI, обеспечивая консистентность: AI всегда знает про `declare(strict_types=1)`, про формат ответов контроллеров, про naming conventions.

Выигрыш: устранение повторяющихся инструкций в промптах. Без steering AI периодически «забывал» про strict_types или генерировал DTO без `toArray()/fromArray()`.

#### Этап 3: Инкрементальная реализация (Autopilot)

Каждый слой реализовывался последовательно с проверкой:

1. **Domain Layer** → запуск тестов → ✅
2. **DTO** → запуск тестов → ✅
3. **Repository** (in-memory) → запуск тестов → ✅
4. **Service Layer** → запуск тестов → ✅
5. **Controller Layer** → запуск тестов → ✅
6. **Property-based тесты** → запуск → обнаружен баг → исправление → ✅
7. **Frontend** → `npm run build` → ✅

Типичный промпт: «Реализуй Service Layer (CompanyService, EventService, ActionService) согласно design.md. Сервисы координируют Domain + Repository. Проверь тестами.»

AI генерировал код, запускал тесты в Docker, при ошибках — исправлял и перезапускал. Среднее количество итераций на слой: 1-2 (первая генерация + мелкие правки).

#### Этап 4: Property-based тестирование

AI сгенерировал 10 property-based тестов на основе correctness properties из design.md:

- Генераторы случайных данных (Eris): стадии, действия, временные интервалы (0-365 дней)
- Каждое свойство проверяется на 100+ случайных входах
- Тесты аннотированы ссылками на требования (`Validates: Requirements X.Y`)

Именно на этом этапе property-based тест #4 (Demo freshness) выявил баг: `validateDemoDone()` использовал `new \DateTimeImmutable()` внутри, что делало тест недетерминированным при граничных значениях (ровно 60 дней). Решение: добавлен метод `validateDemoDoneAt(\DateTimeImmutable $now)` для инъекции времени. Основной метод делегирует в него с текущим временем.

#### Этап 5: Frontend

Промпт: «Создай Next.js 14 SPA с карточкой компании. Зеркалируй бэкенд-логику стадий. Используй Tailwind CSS, Framer Motion, Lucide React. Мок-данные вместо API.»

AI сгенерировал полный фронтенд за одну сессию: страницы, компоненты, типы, мок-данные. Потребовалось 2-3 итерации для доработки UX (shake-анимация на заблокированных действиях, toast-уведомления, модальное подтверждение Null).

### Контроль качества и рисков

| Риск | Как контролировали | Пример |
|------|-------------------|--------|
| **Галлюцинации** | Каждый блок кода проверяется запуском тестов в Docker. Не доверяем — проверяем | AI сгенерировал `validateDemoDone()` с `new \DateTimeImmutable()` внутри — тест поймал недетерминизм |
| **Дрейф архитектуры** | Steering-файлы фиксируют паттерны. AI не может «забыть» про strict_types или формат ответов | Без steering AI генерировал контроллеры без `['success' => bool]` обёртки |
| **Несогласованность фронт/бэк** | Фронтенд зеркалирует бэкенд: `stages.ts` повторяет `StageMap.php`, `ActionRestrictions.php` | При изменении ограничений Aware обновлены оба файла одновременно |
| **Безопасность** | Optimistic locking, валидация входных данных, типизированные DTO, `declare(strict_types=1)` | `CompanyRepository::updateStage()` проверяет `expectedStage` перед записью |
| **Лицензии** | Только MIT/BSD-зависимости | PHPUnit (BSD-3), Eris (MIT), Next.js (MIT), Tailwind (MIT), Framer Motion (MIT) |
| **Трассировка** | Каждый тест аннотирован `Validates: Requirements X.Y` | `DemoFreshnessTest` → Requirements 7.2, 7.3 |

### Где AI реально дал выигрыш

| Область | Без AI (оценка) | С AI (факт) | Выигрыш |
|---------|----------------|-------------|---------|
| Спецификация (requirements + design + tasks) | 2-3 часа | ~15 мин | 8-12x |
| Domain Layer (5 классов, ~400 строк) | 3-4 часа | ~30 мин | 6-8x |
| Property-based тесты (10 свойств, генераторы) | 4-5 часов | ~40 мин | 6-7x |
| Unit-тесты (40+ тестов) | 3-4 часа | ~30 мин | 6-8x |
| Frontend (8 компонентов, 3 страницы) | 8-10 часов | ~1.5 часа | 5-7x |
| SQL-схема + Docker | 1-2 часа | ~15 мин | 4-8x |
| Документация (README) | 1-2 часа | ~20 мин | 3-6x |

Наибольший выигрыш — в генерации тестов и спецификации. AI хорошо справляется с шаблонным кодом (DTO, тесты, CRUD), но требует внимательной проверки бизнес-логики (пример: баг с demo freshness).

### Что не стоит доверять AI без проверки

- **Граничные условия** — баг с 60-дневным правилом был пойман только property-based тестом
- **Согласованность ограничений** — противоречие в ТЗ между ограничениями Aware и условием выхода не было замечено AI автоматически
- **Архитектурные решения** — AI предлагал DI-контейнер и абстрактные репозитории, что было избыточно для прототипа

## Что бы улучшил

- **Интеграция с Joomla**: замена in-memory репозиториев на Joomla Database API (`DatabaseInterface`)
- **Авторизация**: ACL через Joomla Access, разграничение по менеджерам
- **Интеграции**: webhook-уведомления при переходах, интеграция с телефонией для автоматической фиксации звонков
- **Мониторинг**: метрики конверсии по стадиям, среднее время на стадии, bottleneck-анализ
- **UX**: drag-and-drop по стадиям на канбан-доске, inline-редактирование полей компании
- **AI-workflow**: добавить Kiro Hooks для автоматического запуска тестов при сохранении PHP-файлов, автоматическую синхронизацию ограничений между бэкендом и фронтендом

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
