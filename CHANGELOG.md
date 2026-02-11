# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-11

### Added

- Domain Layer: StageMap, StageEngine, TransitionValidator, ActionRestrictions
- 10 стадий воронки: Ice → Touched → Aware → Interested → demo_planned → Demo_done → Committed → Customer → Activated + Null
- Строго последовательные переходы с валидацией условий выхода
- Ограничения действий по стадиям
- Правило 60 дней для Demo_done (demo freshness)
- Null-стадия: достижима из любой активной стадии, терминальна
- Optimistic locking при обновлении стадии
- Service Layer: CompanyService, EventService, ActionService
- API Layer: CompanyController, EventController, ActionController
- REST API: 6 эндпоинтов с JSON-ответами и кодами ошибок
- DTO: CompanyDTO, CompanyCardDTO, Event, TransitionResult, ValidationResult
- Repository: in-memory реализация (CompanyRepository, EventRepository)
- Event sourcing lite: append-only журнал событий
- 10 property-based тестов (Eris/PHPUnit, 100+ итераций каждый)
- 40+ unit-тестов для всех переходов стадий
- Docker-окружение: PHP 8.2, MariaDB 10.6
- SQL-схема: 6 таблиц с индексами
- Frontend: Next.js 14 SPA (static export)
  - Карточка компании с прогресс-баром стадий
  - Инструкция менеджера с копированием
  - Доступные/заблокированные действия (shake-анимация)
  - История событий с фильтрацией по типу
  - Переход на следующую стадию и в Null (с подтверждением)
  - Toast-уведомления
  - Канбан-доска воронки продаж
  - Список компаний с поиском и фильтрацией
- CI/CD: GitHub Actions (тесты + деплой на GH Pages)
- Kiro Specs: requirements.md, design.md, tasks.md
