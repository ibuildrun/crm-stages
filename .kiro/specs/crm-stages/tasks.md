# Implementation Plan: CRM Stages

## Overview

Реализация бэкенда CRM-компонента для Joomla 4 с чистым доменным слоем, REST API и property-based тестами. Фронтенд не реализуется — только создаётся папка-заглушка. Все задачи инкрементальны: каждая строится на предыдущей.

## Tasks

- [x] 1. Инициализация проекта и инфраструктура
  - [x] 1.1 Создать структуру директорий проекта
    - Создать корневую структуру: `src/`, `tests/`, `frontend/` (пустая папка-заглушка), `docker/`
    - Внутри `src/`: `Domain/`, `Service/`, `Repository/`, `Controller/`, `DTO/`
    - Внутри `tests/`: `Unit/`, `Property/`
    - _Requirements: общая архитектура из design.md_
  - [x] 1.2 Настроить composer.json с зависимостями
    - PHPUnit 10+, Eris (property-based testing), PSR-4 autoloading
    - Namespace: `GlavPro\CrmStages`
    - _Requirements: Testing Strategy из design.md_
  - [x] 1.3 Настроить phpunit.xml
    - Конфигурация тестовых suite: Unit и Property
    - Bootstrap через composer autoload
    - _Requirements: Testing Strategy из design.md_
  - [x] 1.4 Создать Docker-окружение
    - docker-compose.yml: PHP 8.2+, MariaDB 10.6+, Joomla 4
    - Dockerfile для PHP с необходимыми расширениями
    - SQL-скрипт инициализации таблиц из Data Models
    - _Requirements: ТЗ п.1 — Docker_
  - [x] 1.5 Создать SQL-миграцию для всех таблиц
    - `#__crmstages_companies`, `#__crmstages_events`, `#__crmstages_discovery`, `#__crmstages_demos`, `#__crmstages_invoices`, `#__crmstages_certificates`
    - Все индексы из Data Models
    - _Requirements: Data Models из design.md_

- [x] 2. Domain Layer — StageMap и базовые DTO
  - [x] 2.1 Реализовать StageInfo и StageMap
    - Класс `StageInfo`: code, mlsCode, name, instruction, exitConditions, restrictions
    - Класс `StageMap`: getOrderedStages(), getNextStage(), isTerminal(), getStageIndex(), getStageInfo()
    - Полная конфигурация всех 10 стадий (Ice → Activated + Null) с MLS-кодами из ТЗ
    - _Requirements: 1.1_
  - [x] 2.2 Реализовать DTO-классы
    - `CompanyDTO`, `CompanyCardDTO`, `TransitionResult`, `ValidationResult`, `Event`
    - Методы `toArray()` и `fromArray()` для сериализации/десериализации
    - _Requirements: 12.1, 12.2_
  - [x] 2.3 Property-тест: Company state round-trip
    - **Property 9: Company state serialization round-trip**
    - **Validates: Requirements 12.3**
  - [x] 2.4 Property-тест: Event round-trip
    - **Property 10: Event serialization round-trip**
    - **Validates: Requirements 12.5**

- [x] 3. Domain Layer — TransitionValidator
  - [x] 3.1 Реализовать TransitionValidator
    - Метод `validate(string $currentStage, array $events): ValidationResult`
    - Логика проверки условий выхода для каждой стадии:
      - Ice: есть событие lpr_conversation
      - Touched: есть событие discovery_filled
      - Aware: есть событие demo_planned
      - Interested: есть событие demo_planned с датой
      - demo_planned: есть событие demo_conducted
      - Demo_done: есть invoice_created или kp_sent, демо < 60 дней
      - Committed: есть payment_received
      - Customer: есть certificate_issued
    - _Requirements: 1.2, 2.3, 2.4, 3.3, 3.4, 4.3, 4.4, 5.3, 5.4, 6.3, 6.4, 7.2, 7.3, 7.4, 7.5, 8.2, 8.3, 9.2, 9.3_
  - [x] 3.2 Property-тест: Transition validity
    - **Property 1: Transition validity — transition succeeds iff exit conditions met**
    - **Validates: Requirements 1.2, 1.3**
  - [x] 3.3 Property-тест: Demo freshness constraint
    - **Property 4: Demo freshness constraint — 60-day window**
    - **Validates: Requirements 7.2, 7.3**

- [x] 4. Domain Layer — StageEngine и ActionRestrictions
  - [x] 4.1 Реализовать ActionRestrictions
    - Методы: getAllowedActions(), getRestrictedActions(), isActionAllowed()
    - Конфигурация ограничений по стадиям из ТЗ
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_
  - [x] 4.2 Реализовать StageEngine
    - Конструктор с TransitionValidator и StageMap
    - `transition(Company, events)`: проверка условий → переход → результат
    - `transitionToNull(Company)`: переход в Null из любой активной стадии
    - `getAvailableActions(stageCode)`: делегация в ActionRestrictions
    - _Requirements: 1.2, 1.3, 1.5, 1.6, 13.1, 13.4_
  - [x] 4.3 Property-тест: Sequential transitions only
    - **Property 2: Sequential transitions — no stage skipping**
    - **Validates: Requirements 1.5, 1.6**
  - [x] 4.4 Property-тест: Action restrictions
    - **Property 3: Action restrictions enforced per stage**
    - **Validates: Requirements 3.2, 4.2, 5.2, 14.1, 14.2, 14.3, 14.4, 14.5**
  - [x] 4.5 Property-тест: Null stage behavior
    - **Property 5: Null stage reachable from any active stage and terminal**
    - **Validates: Requirements 13.1, 13.4**

- [x] 5. Checkpoint — Domain Layer
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Service Layer и Repository
  - [x] 6.1 Реализовать EventRepository
    - CRUD для событий: insert, findByCompanyId, findByCompanyIdAndType
    - Сортировка по created_at DESC
    - _Requirements: 10.1, 10.2, 10.3, 10.4_
  - [x] 6.2 Реализовать CompanyRepository
    - CRUD для компаний: findById, updateStage (с optimistic locking)
    - _Requirements: 1.4_
  - [x] 6.3 Реализовать EventService
    - `recordEvent()`: валидация + запись через репозиторий
    - `getEvents()`: получение с опциональной фильтрацией
    - _Requirements: 10.1, 10.2, 10.3, 10.4_
  - [x] 6.4 Реализовать CompanyService
    - `getCompany()`, `transitionStage()`, `transitionToNull()`, `getCompanyCard()`
    - Оркестрация: StageEngine + EventService + Repository
    - _Requirements: 1.2, 1.4, 11.1, 11.2, 11.3, 11.4, 11.5_
  - [x] 6.5 Реализовать ActionService
    - `executeAction()`: проверка ограничений + запись события
    - Поддержка действий: call, fill_discovery, plan_demo, conduct_demo, create_invoice, send_kp, record_payment, issue_certificate
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_
  - [x] 6.6 Property-тест: Event completeness
    - **Property 6: Event completeness — all events contain required fields**
    - **Validates: Requirements 10.2**
  - [x] 6.7 Property-тест: Event ordering
    - **Property 7: Event ordering — reverse chronological**
    - **Validates: Requirements 10.3**
  - [x] 6.8 Property-тест: Event filtering
    - **Property 8: Event filtering — type match**
    - **Validates: Requirements 10.4**

- [x] 7. API Layer — Controllers
  - [x] 7.1 Реализовать CompanyController
    - GET `/api/companies/{id}` → CompanyService::getCompanyCard()
    - POST `/api/companies/{id}/transition` → CompanyService::transitionStage()
    - POST `/api/companies/{id}/transition-null` → CompanyService::transitionToNull()
    - JSON-ответы, обработка ошибок по Error Handling из design.md
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_
  - [x] 7.2 Реализовать EventController
    - GET `/api/companies/{id}/events` → EventService::getEvents()
    - Поддержка query-параметра `type` для фильтрации
    - _Requirements: 10.3, 10.4_
  - [x] 7.3 Реализовать ActionController
    - POST `/api/companies/{id}/actions/{action}` → ActionService::executeAction()
    - Валидация входных данных, обработка ошибок
    - _Requirements: 14.5_

- [x] 8. Checkpoint — Full Backend
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Unit-тесты для каждого перехода стадии
  - [x] 9.1 Unit-тесты переходов Ice → Touched → Aware → Interested
    - Позитивные и негативные сценарии для каждого перехода
    - _Requirements: 2.3, 2.4, 3.3, 3.4, 4.3, 4.4_
  - [x] 9.2 Unit-тесты переходов demo_planned → Demo_done → Committed
    - Включая правило 60 дней
    - _Requirements: 5.3, 5.4, 6.3, 6.4, 7.2, 7.3, 7.4, 7.5_
  - [x] 9.3 Unit-тесты переходов Committed → Customer → Activated и Null
    - Включая терминальность Null
    - _Requirements: 8.2, 8.3, 9.2, 9.3, 13.1, 13.2, 13.3, 13.4_

- [x] 10. Docker и README
  - [x] 10.1 Финализировать Docker-окружение
    - Проверить запуск через docker-compose up
    - Убедиться что тесты проходят в контейнере
    - _Requirements: ТЗ п.1_
  - [x] 10.2 Написать README.md
    - Архитектура, модель данных, инструкция запуска (1-2 команды), тесты, AI-workflow
    - _Requirements: ТЗ п.7_

- [x] 11. Final checkpoint
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- All tasks are required — comprehensive testing from the start
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (Eris/PHPUnit)
- Unit tests validate specific examples and edge cases
- Frontend folder (`frontend/`) created as placeholder — implementation deferred
