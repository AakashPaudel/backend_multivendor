# Multi-Vendor Backend Deployment Guide

This guide covers the Docker-based deployment and operational expectations for the current backend implementation.

## Runtime Services

Expected service names:

- `app`
- `web`
- `queue`
- `scheduler`
- `db`
- `redis`

## Production Environment Checklist

Set these before deployment:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=<public-api-url>`
- `APP_FRONTEND_URL=<frontend-url>`
- `APP_KEY=<generated-secret>`
- `DB_CONNECTION=mysql`
- `DB_HOST=db`
- `DB_PORT=3306`
- `DB_DATABASE=<production-db>`
- `DB_USERNAME=<production-user>`
- `DB_PASSWORD=<production-password>`
- `REDIS_HOST=redis`
- `REDIS_PORT=6379`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_FAILED_DRIVER=database-uuids`
- `MAIL_MAILER=<smtp-or-provider>`
- `MAIL_HOST=<provider-host>`
- `MAIL_PORT=<provider-port>`
- `MAIL_USERNAME=<provider-username>`
- `MAIL_PASSWORD=<provider-password>`
- `MAIL_ENCRYPTION=<tls-or-ssl>`
- `MAIL_FROM_ADDRESS=<verified-from-address>`
- `MAIL_FROM_NAME=<app-name>`
- `FILESYSTEM_DISK=local`
- `ESEWA_MODE=sandbox|production`
- `ESEWA_BASE_URL=<gateway-base-url>`
- `ESEWA_FORM_URL=<gateway-form-url>`
- `ESEWA_STATUS_CHECK_URL=<gateway-status-url>`
- `ESEWA_MERCHANT_CODE=<merchant-code>`
- `ESEWA_SECRET_KEY=<merchant-secret>`
- `ESEWA_SUCCESS_URL=<public-api-url>/api/payments/esewa/success`
- `ESEWA_FAILURE_URL=<public-api-url>/api/payments/esewa/failure`

## Filesystem Expectations

- Public media is stored in `storage/app/public/`.
- Public media must be exposed through `public/storage`.
- Private files such as invoices are stored in `storage/app/private/`.
- Private files are served only through authorized controller actions.
- If moving to S3 later, keep business logic on Laravel's Filesystem abstraction and remap disks through config.

## Docker Readiness Checklist

- Build images with production-safe PHP extensions and Composer install output.
- Ensure the `app` container can run `php artisan optimize`, `php artisan migrate --force`, and `php artisan config:cache`.
- Ensure `web` serves the Laravel public directory and preserves `public/storage`.
- Ensure `queue` runs `php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3`.
- Ensure `scheduler` runs `php artisan schedule:run --verbose --no-interaction` every minute.
- Mount persistent volumes for:
  - `storage/app/public`
  - `storage/app/private`
  - `storage/logs` if log persistence is required outside container stdout/stderr
- Ensure MySQL and Redis data volumes are persistent.

## Deployment Procedure

1. Build and start the Docker services.
2. Confirm `.env` contains production values and secrets.
3. Generate or inject `APP_KEY` if not already present.
4. Run migrations:
   - `docker compose exec app php artisan migrate --force`
5. Seed only the required bootstrap data when needed:
   - `docker compose exec app php artisan db:seed --class=Database\\\\Seeders\\\\DatabaseSeeder --force`
6. Run Laravel optimization commands:
   - `docker compose exec app php artisan config:cache`
   - `docker compose exec app php artisan route:cache`
   - `docker compose exec app php artisan event:cache`
7. Ensure `public/storage` exists:
   - `docker compose exec app php artisan storage:link`
8. Verify queue and scheduler services are running.
9. Run a smoke test against `/up`, `/api/categories`, and one authenticated route.

## Health and Runtime Visibility

- Laravel health endpoint: `GET /up`
- DB connectivity is validated through app boot plus migration/test commands.
- Redis connectivity is validated through queue, cache, and session usage.
- Scheduler heartbeat is logged every five minutes as `scheduler.heartbeat`.
- Payment lifecycle events are logged with `payment.*`.
- Critical admin and commerce mutations are written to the `audit_logs` table.

## Queue and Failed Job Operations

- Start a worker manually:
  - `docker compose exec app php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3`
- Inspect failed jobs:
  - `docker compose exec app php artisan queue:failed`
- Retry all failed jobs:
  - `docker compose exec app php artisan queue:retry all`
- Retry selected failed jobs:
  - `docker compose exec app php artisan queue:retry <id>`
- Prune old failed jobs:
  - `docker compose exec app php artisan queue:prune-failed --hours=48`

## Scheduler Operations

Scheduled commands currently expected in production:

- `recommendations:generate --limit=8` hourly
- `payments:cleanup-stale-pending --hours=24` every six hours
- `payments:reconcile-pending --hours=1 --limit=25` every thirty minutes
- `queue:prune-failed --hours=48` daily

Manual runs:

- `docker compose exec app php artisan recommendations:generate --limit=8`
- `docker compose exec app php artisan payments:cleanup-stale-pending --hours=24`
- `docker compose exec app php artisan payments:reconcile-pending --hours=1 --limit=25`

## Payment Incident Runbook

Use this sequence for payment support incidents:

1. Check `payments` table rows for the affected `order_number` and `transaction_uuid`.
2. Review `audit_logs` for `payment.*` entries.
3. Review application logs for `payment.verified`, `payment.failed`, `payment.pending_review`, or `payment.cancelled`.
4. If a payment is still pending, run:
   - `docker compose exec app php artisan payments:reconcile-pending --hours=1 --limit=25`
5. If pending attempts have clearly expired, run:
   - `docker compose exec app php artisan payments:cleanup-stale-pending --hours=24`
6. Never mark an order paid from redirect data alone.
7. Never re-run manual stock deductions outside the payment finalization path.

## Production Hardening Checklist

- `APP_DEBUG=false`
- secrets provided through environment variables or secret management
- HTTPS termination at the proxy/load balancer layer
- Sanctum tokens transmitted only over HTTPS in production
- Redis-backed cache, queue, and session confirmed
- queue worker monitored and restarted automatically
- scheduler container running continuously
- writable storage directories confirmed
- mail sender identity configured
- eSewa production endpoints and credentials verified before go-live
- backups handled at the infrastructure/database layer

## Final Verification Commands

- Full test suite:
  - `docker compose exec app php artisan test --compact`
- API routes:
  - `docker compose exec app php artisan route:list --path=api`
- Config smoke checks:
  - `docker compose exec app php artisan config:show app.env`
  - `docker compose exec app php artisan config:show app.debug`
  - `docker compose exec app php artisan config:show database.default`
  - `docker compose exec app php artisan config:show queue.default`
  - `docker compose exec app php artisan config:show cache.default`

## Current MVP Notes

- Invoices are private HTML files.
- Search is SQL-based.
- Recommendations use the internal co-purchase generator and do not depend on external search infrastructure.
- Docker remains the supported deployment model for this backend.
