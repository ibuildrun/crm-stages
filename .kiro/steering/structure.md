# Project Structure

## Architecture

Layered architecture with strict dependency direction: Controller → Service → Domain + Repository. The Domain layer has zero external dependencies (pure PHP).

```
src/
├── Controller/     # API layer: HTTP request → JSON response
├── Service/        # Orchestration: coordinates Domain + Repository
├── Domain/         # Pure business logic (no framework deps)
├── DTO/            # Immutable data transfer objects with toArray()/fromArray()
└── Repository/     # Data access (in-memory mock, replaceable)

frontend/src/
├── app/            # Next.js App Router pages
│   ├── page.tsx              # Dashboard / home
│   ├── companies/page.tsx    # Company list
│   └── companies/[id]/       # Company detail card
├── components/     # Reusable React components
├── icons/          # SVG icon components
└── lib/            # Types, mock data, stage definitions

tests/
├── Unit/           # Scenario-based tests per stage group (Early/Mid/Late)
└── Property/       # Property-based tests (Eris) for invariants

docker/             # Dockerfiles and init.sql for MariaDB schema
```

## Key Patterns

- All PHP classes use `final` for domain/DTO classes and `readonly` constructor promotion
- DTOs implement `toArray()` and static `fromArray()` for serialization round-trips
- Controllers return `['success' => bool, 'data'|'errors' => ...]` response format
- Domain classes are instantiated via constructor injection (no DI container yet)
- `StageMap` is the single source of truth for stage order, exit conditions, and restrictions
- `StageEngine` is the main entry point for transition logic
- `TransitionValidator` handles per-stage exit condition checks
- `ActionRestrictions` defines which actions are blocked at each stage
- Tests instantiate domain objects directly — no mocking frameworks used
- Frontend mirrors backend stage logic in `lib/stages.ts` and `lib/types.ts`
- `frontend/original-design/` contains the original Vite/React prototype (excluded from tsconfig)
