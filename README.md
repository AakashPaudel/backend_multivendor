# Multi-Vendor Ecommerce Backend

Laravel backend for a multi-vendor ecommerce final year project.

Primary implementation references:

- `docs/multi_vendor_backend_implementation.md`
- `docs/multi_vendor_database_architecture.md`
- `docs/multi_vendor_backend_todo.md`
- `docs/multi_vendor_backend_api_guide.md`
- `docs/multi_vendor_backend_deployment_guide.md`
- `docs/multi_vendor_payment_guide.md`

## Runtime

- PHP `8.3`
- Laravel `13`
- MySQL `8.4`
- Redis `7`
- Docker Compose services: `app`, `web`, `queue`, `scheduler`, `db`, `redis`

## Local Commands

Run all commands through Docker:

```bash
docker compose up -d
docker compose exec app php artisan --version
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test
docker compose exec app php artisan route:list --path=api
docker compose exec app php artisan queue:work
docker compose exec app php artisan schedule:run
docker compose exec app php artisan recommendations:generate --limit=8
docker compose exec app php artisan payments:cleanup-stale-pending --hours=24
docker compose exec app php artisan payments:reconcile-pending --hours=1 --limit=25
docker compose exec app composer test
```

## Developer Guides

- API integration and route groups: `docs/multi_vendor_backend_api_guide.md`
- eSewa payment flow: `docs/multi_vendor_payment_guide.md`
- Docker deployment and operations: `docs/multi_vendor_backend_deployment_guide.md`
- Backend execution checklist: `docs/multi_vendor_backend_todo.md`

## Current Bootstrap Status

- Laravel app initialized in repo root
- Sanctum installed via `php artisan install:api`
- Laravel Boost installed
- Docker-aware `.env` and `.env.example` restored
- Redis configured for queue, cache, and session drivers

## Implementation Notes

- backend only
- API routes live under `/api`
- use Form Requests, Policies, API Resources, Services, and Actions
- keep controllers thin
- critical commerce flows will use DB transactions and idempotent payment handling
- public uploads use Laravel's `public` disk and `public/storage`
- private artifacts such as invoices use Laravel's private `local` disk and controller-mediated delivery
