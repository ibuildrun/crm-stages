# Tech Stack & Build

## Backend

- PHP 8.2 with `declare(strict_types=1)` in every file
- Joomla 4 component architecture (but domain logic is framework-independent)
- PSR-4 autoloading via Composer (`GlavPro\CrmStages\` → `src/`)
- PHPUnit 10.5 for testing
- Eris 1.x for property-based testing
- Repositories are currently in-memory (mock), designed to be swapped for Joomla DB API

## Frontend

- Next.js 14 with `output: 'export'` (static site generation)
- React 18, TypeScript (strict mode)
- Tailwind CSS 3.4 with dark mode (`class` strategy)
- Framer Motion for animations
- Lucide React for icons
- Path alias: `@/*` → `./src/*`
- Currently uses mock data; production will call the backend REST API

## Infrastructure

- Docker: PHP 8.2-cli for backend, Node 20-alpine for frontend
- MariaDB 10.6 for database
- docker-compose orchestrates all three services

## Common Commands

```bash
# Backend tests (all) — via Docker
docker build -t crm-stages-test -f docker/Dockerfile .
docker run --rm crm-stages-test vendor/bin/phpunit

# Backend tests by suite
docker run --rm crm-stages-test vendor/bin/phpunit --testsuite=Unit
docker run --rm crm-stages-test vendor/bin/phpunit --testsuite=Property

# Backend tests — local (requires PHP + Composer)
composer install
vendor/bin/phpunit
composer test          # alias for phpunit
composer test:unit     # unit tests only
composer test:property # property-based tests only

# Frontend
cd frontend
npm install
npm run build   # static export → frontend/out/
npm run dev     # dev server at localhost:3000

# Full environment
docker-compose up -d
```
