# BACKEND_TODO

This checklist now reflects the backend as implemented through Phase 10.

Source of truth used:

- [docs/multi_vendor_backend_implementation.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_implementation.md)
- [docs/multi_vendor_database_architecture.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_database_architecture.md)

Current completion status:

- [x] The backend is implemented, tested in Docker, documented, and prepared for Docker-based deployment.
- [x] Non-critical MVP decisions are explicit rather than left as ambiguous TODO items.

Resolved MVP decisions:

- [x] Invoices are generated as private HTML files, not PDFs.
- [x] Search remains SQL-based for the MVP.
- [x] Recommendations use the built-in co-purchase generator and persistence layer.
- [x] Files use Laravel Filesystem with public media on `storage/app/public` and private artifacts on `storage/app/private`.

---

## 1. Foundation and Setup

Objective:

- establish the Laravel backend codebase, repository conventions, and implementation scaffolding

- [x] Laravel initialized in the repository root
- [x] Backend folder structure created for actions, services, requests, resources, policies, notifications, jobs, and support classes
- [x] Sanctum installed and configured
- [x] Laravel Boost installed
- [x] Redis configured for queue, cache, and session
- [x] API-only JSON exception handling established
- [x] Code style tooling established with Pint
- [x] Local backend commands documented

Verify:

- [x] `artisan` runs inside Docker
- [x] Laravel boots successfully in Docker
- [x] JSON API failures return JSON instead of HTML

Done when:

- [x] foundational backend scaffolding is stable and ready for domain features

---

## 2. Containerization and Environment

Objective:

- align Laravel with the existing Docker runtime and formalize environment handling

- [x] Docker service names aligned to `app`, `web`, `queue`, `scheduler`, `db`, `redis`
- [x] Laravel configured to use `DB_HOST=db` and `REDIS_HOST=redis`
- [x] `.env.example` covers backend, test, mail, storage, queue, and eSewa variables
- [x] Secret values remain environment-driven and uncommitted
- [x] Storage directories are writable in containers
- [x] `storage:link` behavior is compatible with the current Docker entrypoint flow
- [x] Public and private storage strategy documented and implemented
- [x] Deployment env checklist documented in [docs/multi_vendor_backend_deployment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_deployment_guide.md)
- [x] Health endpoint available at `/up`
- [x] Queue and scheduler operational behavior documented

Verify:

- [x] app connects to MySQL and Redis using Docker service names
- [x] queue worker starts successfully
- [x] scheduler starts successfully
- [x] environment coverage is documented for local and production

Done when:

- [x] Docker-aligned runtime and environment setup are production-usable

---

## 3. Core Architecture and Conventions

Objective:

- keep backend implementation consistent across all domains

- [x] Route grouping established under `/api`
- [x] Auth, admin, vendor, customer, catalog, cart, checkout, payment, order, and recommendation route groups exist
- [x] Controllers stay thin and delegate to services/actions
- [x] Form Requests handle validation
- [x] Policies and middleware handle authorization
- [x] API Resources shape responses
- [x] Core enums exist for roles and workflow statuses
- [x] State transition rules are enforced in services and requests
- [x] Audit and operational logging conventions are applied on sensitive changes
- [x] Pagination and filtering conventions are in place across public and reporting endpoints

Verify:

- [x] at least one concrete implementation exists for each convention type
- [x] route organization is consistent and readable

Done when:

- [x] future backend work can follow established patterns without inventing new structure

---

## 4. Database and Schema Rollout

Objective:

- implement the full relational schema and persistence layer needed by the backend

- [x] Core and support migrations implemented
- [x] Foreign keys, indexes, and unique constraints applied
- [x] Models and relationships implemented
- [x] Casts and useful scopes added
- [x] Factories added for major entities
- [x] Seeders added for admin bootstrap, settings, and commission defaults
- [x] Test database bootstrap works in Docker

Verify:

- [x] migrations run cleanly from a fresh database
- [x] seeders complete without errors
- [x] factories generate valid test data
- [x] model relationships and scopes behave correctly

Done when:

- [x] schema and model layer fully support all implemented backend domains

---

## 5. Identity, Auth, and RBAC

Objective:

- implement secure account lifecycle flows and role-based access control

- [x] Customer registration implemented
- [x] Vendor registration implemented with pending approval state
- [x] Unified login/logout implemented
- [x] `GET /api/auth/me` implemented
- [x] Forgot-password and reset-password implemented
- [x] Email verification flow implemented
- [x] Admin bootstrap/login supported
- [x] Role middleware enforced for admin, vendor, and customer surfaces
- [x] Auth endpoints rate-limited
- [x] Auth and authorization failures return JSON

Verify:

- [x] customer and vendor registration work
- [x] login/logout work with Sanctum
- [x] password reset and email verification pass
- [x] role separation is enforced

Done when:

- [x] all account lifecycle and RBAC flows are implemented and tested

---

## 6. Core Marketplace Domains

Objective:

- implement profiles, vendor management, catalog management, cart, and checkout preparation

- [x] Customer profile endpoints implemented
- [x] Customer address CRUD implemented with ownership and default address handling
- [x] Vendor profile endpoints implemented
- [x] Admin vendor approve/reject/suspend flow implemented with audit and notification hooks
- [x] Category CRUD implemented for admins plus public category APIs
- [x] Vendor product CRUD implemented with owner-only access
- [x] Admin product moderation implemented
- [x] Public products, vendors, and categories implemented
- [x] Search, filtering, sorting, and pagination implemented
- [x] Cart endpoints implemented with authoritative totals
- [x] Checkout preparation implemented for multi-vendor carts
- [x] Public media uploads validated and stored on the Laravel public disk

Verify:

- [x] unapproved vendors cannot sell
- [x] approved vendors manage only their own products
- [x] public catalog exposes only public and sellable records
- [x] checkout splits internal records by vendor

Done when:

- [x] core marketplace flows are implemented and tested

---

## 7. Payments and eSewa

Objective:

- implement safe, verified, idempotent eSewa payment handling

- [x] eSewa env/config mapping implemented
- [x] `EsewaPaymentService` implemented
- [x] `PaymentFinalizationService` implemented
- [x] initiate, success, failure, verify, and status endpoints implemented
- [x] payment attempts persist transaction UUID, references, raw payloads, verification state, and `paid_at`
- [x] backend verification checks amount and identity
- [x] redirects are never trusted by themselves
- [x] duplicate callbacks are idempotent
- [x] stale pending payment cleanup and reconciliation groundwork implemented
- [x] payment lifecycle audit and operational logs implemented

Verify:

- [x] successful verified payment advances correctly
- [x] failed verification does not produce paid orders
- [x] duplicate callbacks do not duplicate side effects
- [x] amount mismatch and invalid transaction cases fail safely

Done when:

- [x] the full eSewa MVP lifecycle is implemented and test-covered

---

## 8. Orders, Inventory, Commission, and Invoices

Objective:

- implement durable commerce records and safe post-payment order processing

- [x] `OrderService` implemented
- [x] `InventoryService` implemented
- [x] `CommissionService` implemented
- [x] `InvoiceService` implemented
- [x] top-level orders, vendor orders, and order items persist correctly
- [x] product name, SKU, unit price, commission amount, and vendor net amount are snapshotted at order time
- [x] top-level and vendor-order state transitions implemented
- [x] order status history implemented
- [x] customer order APIs implemented
- [x] vendor order APIs implemented with own-record visibility only
- [x] admin order APIs implemented with audited override actions
- [x] stock deduction happens only after verified payment success
- [x] row-locking and transactional stock safety are implemented
- [x] oversell protection is implemented
- [x] commission resolution supports global and vendor-specific configuration
- [x] invoices are generated privately and served through authorized controllers

Verify:

- [x] paid orders are internally consistent
- [x] overselling is prevented
- [x] commission values persist historically
- [x] invoice access control is enforced

Done when:

- [x] order, inventory, commission, and invoice flows are safe and auditable

---

## 9. Search, Recommendations, Notifications

Objective:

- implement discovery enhancements and asynchronous communication support

- [x] public catalog search is production-usable for MVP
- [x] recommendation endpoint implemented
- [x] recommendation generation job/command implemented
- [x] recommendation refresh scheduling implemented
- [x] safe fallback returns empty recommendation results when needed
- [x] queued lifecycle notifications implemented for account creation, vendor decisions, order placed, payment updates, and order status changes
- [x] notification failures are queue-backed and loggable

Verify:

- [x] recommendation endpoint returns valid data or safe empty responses
- [x] recommendation generation persists results
- [x] notification-triggering flows queue correctly

Done when:

- [x] discovery and lifecycle-notification support are implemented without blocking commerce flows

---

## 10. Reporting, Analytics, Admin Controls

Objective:

- provide operational visibility and configuration endpoints for admins and vendors

- [x] `GET /api/admin/dashboard` implemented
- [x] `GET /api/admin/reports` implemented
- [x] `GET /api/vendor/dashboard` implemented
- [x] `GET /api/vendor/reports/sales` implemented
- [x] `GET /api/admin/settings` and `PUT /api/admin/settings` implemented
- [x] `GET /api/admin/commissions` and `PUT /api/admin/commissions` implemented
- [x] `GET /api/admin/users` implemented
- [x] admin-only access is enforced for admin surfaces
- [x] reporting metrics include revenue, commission, vendor counts, payment outcomes, top products, and top vendors
- [x] vendor dashboard includes scoped sales metrics and best-selling products

Verify:

- [x] reporting metrics match DB reality
- [x] vendor reports are scoped to the authenticated vendor
- [x] settings and commission changes persist correctly

Done when:

- [x] admin and vendor reporting surfaces are complete for the MVP

---

## 11. Jobs, Scheduler, Cache, Logging

Objective:

- implement production-usable background processing and operational visibility

- [x] Redis-backed queue usage configured
- [x] queueable jobs used for notifications and recommendations
- [x] payment reconciliation and stale-payment cleanup commands implemented
- [x] scheduled tasks implemented for recommendations, cleanup, reconciliation, failed-job pruning, and heartbeat logging
- [x] queue worker retry policy documented and reflected in the Docker queue worker script
- [x] failed job storage and retry workflow supported
- [x] cache applied for settings, dashboards, recommendations, and public category list
- [x] cache invalidation implemented for settings, dashboards, recommendations, queue failure metrics, and category updates
- [x] audit logging implemented for sensitive actions
- [x] payment and order operational logs implemented
- [x] incident/runbook notes documented in [docs/multi_vendor_backend_deployment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_deployment_guide.md)

Verify:

- [x] queue jobs execute successfully
- [x] scheduled tasks run successfully
- [x] failed jobs can be inspected and retried
- [x] sensitive state changes are visible in logs or audit tables

Done when:

- [x] background operations are supportable in development and deployment

---

## 12. Security, Resilience, and Correctness

Objective:

- harden the backend against authorization gaps, invalid data, unsafe payment handling, and race conditions

- [x] mutation endpoints use Form Requests
- [x] mutation endpoints use authorization policies or role middleware
- [x] public endpoints expose only public/sellable data
- [x] rate limiting exists on auth, verification, and payment-sensitive endpoints
- [x] JSON error responses are standardized for validation, authentication, authorization, throttling, and not-found cases
- [x] critical checkout, payment finalization, and stock deduction flows use DB transactions
- [x] row locking protects stock deduction
- [x] idempotency protections exist for payment finalization
- [x] payment redirects are never treated as final truth
- [x] client totals, prices, and stock are not trusted
- [x] admin override actions are audited
- [x] uploads are validated as images
- [x] public uploads and private artifacts use the correct Laravel disks
- [x] secrets are environment-driven and not exposed in API payloads
- [x] production guidance requires `APP_DEBUG=false`
- [x] payment verification failures degrade safely to recoverable backend states
- [x] exception and operational logging are present for critical flows

Verify:

- [x] authorization bypass attempts fail
- [x] malformed payloads fail cleanly
- [x] concurrent checkout/payment tests do not oversell stock
- [x] duplicate payment verification attempts are safe

Done when:

- [x] the backend is hardened for the implemented commerce scope

---

## 13. Automated Tests

Objective:

- maintain trustable automated coverage for critical backend workflows

- [x] feature tests organized by backend domain
- [x] factories and seed utilities support backend scenarios
- [x] auth flows covered
- [x] vendor and product moderation flows covered
- [x] public catalog and search behavior covered
- [x] cart and checkout workflows covered
- [x] payment lifecycle scenarios covered
- [x] order, invoice, and inventory workflows covered
- [x] reporting, recommendation, and notification flows covered
- [x] hardening coverage includes JSON auth/not-found and throttle behavior
- [x] tests run inside Docker against the test database
- [x] CI-friendly test command exists via `php artisan test --compact` and `composer test`
- [x] full Docker test sweep passes

Verify:

- [x] full automated suite passes in Docker
- [x] test database is isolated from development data
- [x] high-risk flows have success and failure-path coverage

Done when:

- [x] the team can trust automated feedback before deployment

---

## 14. API Documentation and DX

Objective:

- document the backend so frontend and future backend contributors can integrate confidently

- [x] route groups and endpoint inventory documented
- [x] Sanctum auth model documented
- [x] request header expectations documented
- [x] standardized error responses documented
- [x] payment frontend integration documented
- [x] queue and scheduler developer commands documented
- [x] local Docker commands documented
- [x] seed and test workflow documented
- [x] API usage guide added in [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
- [x] payment guide maintained in [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)

Verify:

- [x] a frontend developer can follow the docs to integrate major backend areas
- [x] documented payload expectations match the implemented backend

Done when:

- [x] backend API documentation is actionable for implementation and QA

---

## 15. Deployment Readiness

Objective:

- ensure the backend can be deployed and operated safely in Docker-based environments

- [x] production environment variable checklist documented
- [x] Docker deployment expectations documented
- [x] production-safe config expectations documented
- [x] migration and seed procedure documented
- [x] queue worker and scheduler operational guidance documented
- [x] persistent storage expectations documented
- [x] health endpoint and runtime visibility documented
- [x] failed job and payment incident response documented
- [x] rollback and production hardening expectations documented
- [x] deployment runbook added in [docs/multi_vendor_backend_deployment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_deployment_guide.md)

Verify:

- [x] production env checklist covers backend needs
- [x] deployment procedure is repeatable from docs
- [x] queue, scheduler, DB, Redis, and storage expectations are accounted for

Done when:

- [x] the backend is deployment-ready for the documented Docker model

---

## 16. Final Acceptance Matrix

Objective:

- confirm the backend matches the implementation plan with no critical backend gaps remaining

- [x] backend implementation doc re-reviewed against implemented code
- [x] database architecture doc re-reviewed against implemented schema and persistence rules
- [x] all major route groups exist and are registered
- [x] all major modules exist
- [x] critical services and actions exist for the core business flows
- [x] critical policies, enums, transactions, scheduled jobs, and queue jobs exist
- [x] audit logs and operational traces exist for sensitive actions
- [x] automated tests pass
- [x] API docs are current
- [x] deployment docs are actionable
- [x] no critical backend placeholder or missing endpoint remains in the implemented MVP path
- [x] checklist refined to remove ambiguous non-critical carryover items

Verify:

- [x] a fresh reviewer can trace backend requirements to code, tests, and docs
- [x] there are no critical backend gaps for auth, catalog, checkout, payment, orders, reporting, operations, or deployment in the current MVP scope

Done when:

- [x] every item in this file is complete
- [x] the backend is fully implemented, tested, documented, and deployment-ready for the documented scope
