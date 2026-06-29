# BACKEND_MILESTONES

This file converts [docs/multi_vendor_backend_todo.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_todo.md) into phased implementation milestones and ready-to-use Codex prompts.

Use this together with:

- [docs/multi_vendor_backend_implementation.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_implementation.md)
- [docs/multi_vendor_database_architecture.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_database_architecture.md)
- [docs/multi_vendor_backend_todo.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_todo.md)

Execution rule:

- do milestones in order unless a dependency note explicitly says parallel work is safe

---

## Phase 1. Foundation, Docker Alignment, and Laravel Bootstrap

Goal:

- make the repository a stable Laravel backend running correctly inside the existing Docker environment

Includes:

- Laravel initialization in repo root
- dependency installation
- app structure setup
- `.env.example` completion
- Docker hostname alignment
- Redis/session/cache/queue baseline
- error response baseline
- coding standards baseline

Deliverables:

- Laravel app boots in `app`
- routes, app, database, and test directories exist
- Sanctum and Laravel Boost installed
- local commands documented
- JSON error baseline works

Depends on:

- existing healthy Docker environment only

Exit criteria:

- `artisan` works inside Docker
- Laravel connects to MySQL and Redis using `db` and `redis`
- queue and scheduler containers can boot against the initialized app

Codex prompt:

```text
You are implementing Phase 1 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md

Implement only Phase 1: Foundation, Docker Alignment, and Laravel Bootstrap.

Scope:
- initialize Laravel in the repo root if needed
- install and configure Sanctum
- install Laravel Boost
- align Laravel env/config with Docker service names db and redis
- ensure queue/cache/session use Redis
- create the recommended app structure folders
- complete/update .env.example with required backend variables
- set up JSON-first exception handling for API responses
- add coding standard/tooling baseline if missing
- document local Docker commands in repo docs if needed

Constraints:
- backend only
- keep controllers thin
- do not implement marketplace business domains yet
- do not implement complex schema beyond what bootstrap requires

Verification:
- artisan works
- app boots in Docker
- Redis and DB connectivity are configured correctly
- queue and scheduler can start

Also add or update tests only where needed for bootstrap confidence.
```

---

## Phase 2. Core Architecture, Enums, Base Models, and Schema Rollout

Goal:

- create the backend skeleton and persistence layer that every domain feature depends on

Includes:

- base route grouping
- enums
- policies/gate scaffolding
- API resources/request/service/action conventions
- migrations
- models and relationships
- factories
- seeders
- admin bootstrap
- support tables

Deliverables:

- schema migrated
- models relate correctly
- factories and seeders usable in tests
- status enums/state rules established

Depends on:

- Phase 1

Exit criteria:

- fresh database migration and seeding work
- test database setup works
- base models/enums are stable enough for domain implementation

Codex prompt:

```text
You are implementing Phase 2 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 2: Core Architecture, Enums, Base Models, and Schema Rollout.

Scope:
- create route group structure under /api
- add core enums for roles and statuses
- scaffold policies/gates strategy
- add base requests/resources/services/actions structure
- implement migrations for all required core and support tables
- implement models and relationships
- add casts and useful scopes
- add factories for major entities
- add seeders for admin user, default platform settings, and default commissions
- ensure test DB bootstrap works

Constraints:
- backend only
- use the database architecture doc as schema source of truth
- do not implement full business workflows yet
- prefer additive migrations and clean model structure

Verification:
- migrate fresh works
- seeders work
- factories generate valid data
- model relationships and enums behave correctly

Add tests around schema/model assumptions where useful.
```

---

## Phase 3. Identity, Auth, Customer Profiles, and RBAC

Goal:

- implement secure auth, customer/vendor registration, admin login, and access control

Includes:

- customer registration
- vendor registration
- login/logout
- auth/me
- password reset
- email verification
- customer profiles
- customer addresses
- role middleware
- policies for ownership

Deliverables:

- auth routes operational
- customer and vendor registrations persist correct records
- customer profile/address endpoints work
- role-based route protection enforced

Depends on:

- Phase 2

Exit criteria:

- customer and vendor accounts can be created and authenticated
- admin access is separated
- ownership and role restrictions are enforced in tests

Codex prompt:

```text
You are implementing Phase 3 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 3: Identity, Auth, Customer Profiles, and RBAC.

Scope:
- customer registration
- vendor registration with pending approval state
- login/logout
- auth/me
- forgot password and reset password
- email verification flow
- role middleware
- customer profile endpoints
- customer address CRUD endpoints
- ownership policies and JSON auth/authorization errors
- admin bootstrap/login support

Routes included:
- /api/auth/*
- /api/customer/profile
- /api/customer/addresses*

Constraints:
- use Sanctum
- use Form Requests, Policies, API Resources, Services, and thin controllers
- do not implement vendor approval admin actions yet beyond storing vendor pending status

Verification:
- auth flows pass
- password reset and email verification pass
- customer ownership rules pass
- role separation is enforced

Add full feature tests for all auth/profile/address routes in scope.
```

---

## Phase 4. Vendor Approval, Categories, Product Management, Media

Goal:

- implement the vendor operations and admin controls needed before public catalog and commerce flows

Includes:

- vendor profile update
- admin vendor approval/rejection/suspension
- category CRUD
- vendor product CRUD
- admin product moderation
- product media handling
- slug and SKU generation

Deliverables:

- approved vendor workflow operational
- category management operational
- vendor product management operational
- admin moderation operational

Depends on:

- Phase 3

Exit criteria:

- pending vendors cannot sell
- approved vendors can create sellable products
- categories and products behave correctly in tests

Codex prompt:

```text
You are implementing Phase 4 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 4: Vendor Approval, Categories, Product Management, and Media.

Scope:
- vendor profile read/update endpoints
- admin vendor list/approve/reject/suspend endpoints
- category CRUD for admins
- vendor product CRUD endpoints
- admin product moderation/status endpoint
- file path handling for vendor logo/banner and product images
- slug generation
- SKU generation
- sellability rules tied to approved vendors and product status
- audit/log hooks for approval and moderation actions

Routes included:
- /api/vendor/profile
- /api/admin/vendors*
- /api/categories*
- /api/admin/categories*
- /api/vendor/products*
- /api/admin/products*

Constraints:
- backend only
- validate uploads
- store file paths only
- store public media on Laravel's public disk and expose it via `public/storage`
- reserve Laravel's private disk for protected files such as invoices or documents
- keep controllers thin

Verification:
- pending/rejected/suspended vendors cannot sell
- approved vendors can manage only their own products
- admin category/product controls work
- slug and SKU uniqueness is safe

Add feature and policy tests for vendor/admin/product/category flows.
```

---

## Phase 5. Public Catalog, Vendor Store, Search, and Filtering

Goal:

- expose frontend-consumable public discovery APIs on top of approved vendors and sellable products

Includes:

- public category endpoints
- public product listing/detail
- public vendor listing/detail
- search
- filters
- sorting
- pagination

Deliverables:

- catalog APIs ready for frontend integration
- search/filter/sort baseline complete

Depends on:

- Phase 4

Exit criteria:

- inactive or non-sellable content is excluded
- public listing endpoints are stable and paginated

Codex prompt:

```text
You are implementing Phase 5 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 5: Public Catalog, Vendor Store, Search, and Filtering.

Scope:
- public categories list/detail
- public products list/detail
- public vendors list/detail
- /api/search
- pagination
- category/vendor/price filters
- newest/price/popularity sorts
- only approved-vendor and sellable-product visibility
- API resources for public catalog payloads

Constraints:
- SQL-based search is acceptable for MVP
- do not introduce advanced search infrastructure unless truly necessary
- optimize queries and eager loading

Verification:
- public APIs return only public/sellable data
- filtering and sorting work
- pagination metadata is correct

Add feature tests for public catalog and search behavior.
```

---

## Phase 6. Cart and Checkout Preparation

Goal:

- implement cart behavior and authoritative backend checkout preparation for multi-vendor carts

Includes:

- cart fetch/add/update/remove
- product availability validation
- authoritative price refresh
- checkout validation
- address validation
- vendor split preparation
- preliminary totals and commission calculation
- pending order/payment creation

Deliverables:

- customer cart APIs
- multi-vendor checkout preparation endpoint
- transactional creation of pending commerce records

Depends on:

- Phase 5

Exit criteria:

- cart is backend-authoritative
- checkout creates pending order, vendor orders, order items, and initiated payment safely

Codex prompt:

```text
You are implementing Phase 6 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 6: Cart and Checkout Preparation.

Scope:
- GET /api/cart
- POST /api/cart/items
- PUT /api/cart/items/{id}
- DELETE /api/cart/items/{id}
- cart validation and authoritative totals
- POST /api/checkout
- validate authenticated customer and shipping address
- refetch prices and stock from DB
- calculate subtotal/discount/shipping/tax/grand total
- calculate commission preview
- create pending top-level order, vendor orders, order items, and initiated payment in a transaction
- return frontend-ready payment initiation data

Constraints:
- checkout must support multi-vendor carts
- client totals/prices are never trusted
- use services/actions and DB transactions

Verification:
- invalid products/quantities fail correctly
- cart totals are authoritative
- checkout splits vendor records correctly

Add feature tests for cart and checkout preparation workflows.
```

---

## Phase 7. eSewa Payment Integration and Idempotent Finalization

Goal:

- implement the full payment lifecycle with safe verification and side-effect control

Includes:

- payment initiate
- success/failure handlers
- verify endpoint
- gateway payload generation
- gateway verification
- payment ledger
- idempotent finalization

Deliverables:

- eSewa sandbox flow integrated
- payment verification path operational
- duplicate callback protection in place

Depends on:

- Phase 6

Exit criteria:

- orders are only paid after backend verification
- duplicate callbacks are harmless

Codex prompt:

```text
You are implementing Phase 7 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 7: eSewa Payment Integration and Idempotent Finalization.

Scope:
- payment config/env mapping for eSewa
- EsewaPaymentService
- PaymentFinalizationService
- /api/payments/esewa/initiate
- /api/payments/esewa/success
- /api/payments/esewa/failure
- /api/payments/esewa/verify
- /api/payments/{orderNumber}/status
- persist transaction_uuid, gateway refs, raw payloads, verification state, paid_at
- backend verification with amount and identity checks
- idempotent finalization and duplicate callback safety
- payment lifecycle logging/audit hooks

Constraints:
- never trust redirect alone
- do not mark payment paid before backend verification
- keep all side effects idempotent

Verification:
- successful verified payment advances correctly
- failed verification does not create paid orders
- duplicate callbacks do not duplicate side effects
- amount mismatch and invalid transaction cases fail safely

Add feature tests for all payment scenarios in scope.
```

---

## Phase 8. Orders, Inventory Safety, Commission, and Invoices

Goal:

- finish the commerce core after payment verification

Includes:

- full order lifecycle
- vendor order lifecycle
- order status history
- inventory deduction safety
- oversell prevention
- commission persistence
- invoice endpoint/generation
- customer/vendor/admin order views

Deliverables:

- durable order management
- stock-safe finalization
- invoice delivery
- vendor/admin order operations

Depends on:

- Phase 7

Exit criteria:

- paid orders are consistent across all records
- stock changes are safe and transactional
- invoices are authorized and accurate

Codex prompt:

```text
You are implementing Phase 8 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 8: Orders, Inventory Safety, Commission, and Invoices.

Scope:
- OrderService
- InventoryService
- CommissionService
- InvoiceService
- top-level order status transitions
- vendor order status transitions
- order status history
- customer order list/detail
- vendor order list/detail/status update
- admin order list/detail/status update
- row locking or equivalent safe stock strategy
- deduct stock only after verified payment success
- optional stock restore groundwork
- global and vendor-specific commission handling
- invoice endpoint and output generation
- private invoice storage and controller-mediated delivery

Routes included:
- /api/orders*
- /api/vendor/orders*
- /api/admin/orders*
- /api/orders/{orderNumber}/invoice

Constraints:
- transactional correctness first
- vendor visibility restricted to own records
- admin override actions must be logged

Verification:
- paid orders are internally consistent
- overselling is prevented
- commission values persist at order time
- invoice access control is correct

Add feature and concurrency-focused tests where practical.
```

---

## Phase 9. Reporting, Settings, Notifications, Recommendations, and Background Work

Goal:

- implement operator visibility, async communication, recommendations, and background processing

Includes:

- admin dashboard/reports
- vendor dashboard/reports
- platform settings
- commission settings
- notifications
- queue jobs
- scheduler tasks
- recommendation generation and API
- failed job handling
- caching
- audit logging

Deliverables:

- admin/vendor reporting endpoints
- queued notifications
- recommendation groundwork
- scheduler coverage for refresh/cleanup/reconciliation

Depends on:

- Phase 8

Exit criteria:

- reporting values are correct
- async operations run via queue/scheduler
- recommendations are generated from paid order history

Codex prompt:

```text
You are implementing Phase 9 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 9: Reporting, Settings, Notifications, Recommendations, and Background Work.

Scope:
- /api/admin/dashboard
- /api/admin/reports
- /api/vendor/dashboard
- /api/vendor/reports/sales
- /api/admin/settings
- /api/admin/commissions
- /api/admin/users
- queued notifications for vendor/order/payment/account lifecycle events
- recommendation generation job/command
- /api/recommendations/{productId}
- scheduler tasks for recommendation refresh, stale pending payment cleanup, and optional reconciliation
- failed job handling
- cache usage/invalidation for suitable read-heavy data
- audit logging and operational logs

Constraints:
- recommendations must not block core commerce flows
- admin endpoints are admin-only
- vendor reports must be scoped to the authenticated vendor

Verification:
- reporting metrics match DB reality
- queue jobs run correctly
- scheduler tasks run correctly
- recommendation endpoint returns valid results or safe empty responses

Add tests for reporting authorization, recommendation generation, and notification-triggering flows.
```

---

## Phase 10. Hardening, Documentation, Full Test Sweep, and Deployment Readiness

Goal:

- make the backend production-ready and prove completeness against the plan

Includes:

- hardening pass
- rate limiting
- standardized errors finalization
- logs/audit review
- full test completion
- API documentation
- deployment checklist
- production env checklist
- final acceptance review

Deliverables:

- complete automated suite
- docs usable by frontend and operators
- deployment-ready Dockerized backend

Depends on:

- Phases 1 through 9

Exit criteria:

- all tests pass
- docs are current
- deployment readiness checklist is complete
- final acceptance matrix can be checked off

Codex prompt:

```text
You are implementing Phase 10 of the backend for this repository.

Read first:
- docs/multi_vendor_backend_implementation.md
- docs/multi_vendor_database_architecture.md
- docs/multi_vendor_backend_todo.md
- docs/multi_vendor_backend_milestones.md

Implement only Phase 10: Hardening, Documentation, Full Test Sweep, and Deployment Readiness.

Scope:
- final validation/authorization coverage review
- rate limiting review
- standardized JSON error responses finalization
- security and resilience hardening
- full automated test suite completion and cleanup
- API documentation and developer workflow docs
- production env checklist
- Docker deployment readiness checklist
- queue/scheduler operational guidance
- final acceptance review against docs/multi_vendor_backend_todo.md

Constraints:
- do not leave placeholder TODOs in critical paths
- prefer concrete fixes over documenting gaps

Verification:
- full test suite passes in Docker
- docs reflect actual implementation
- deployment checklist is actionable
- final acceptance matrix can be completed with no critical backend gaps
```

---

## Suggested Prompting Order

1. Phase 1
2. Phase 2
3. Phase 3
4. Phase 4
5. Phase 5
6. Phase 6
7. Phase 7
8. Phase 8
9. Phase 9
10. Phase 10

---

## Prompting Tips

- keep each Codex task scoped to one phase unless the current phase is already complete
- always mention:
  - feature/phase name
  - target files or modules
  - routes involved
  - involved tables
  - validation rules
  - authorization rules
  - expected response shape
  - tests required
- after each phase, update [docs/multi_vendor_backend_todo.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_todo.md) progress
- do not start deployment readiness before the full test sweep is stable
