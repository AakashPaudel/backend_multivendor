# Multi-Vendor Ecommerce Backend Implementation

## 1. Purpose

This file is the backend-only implementation source of truth for this repository.

It is intended to give Codex enough detail to implement the Laravel backend later without having to infer routes, modules, service boundaries, workflow rules, authorization patterns, or testing expectations.

This document should be used together with:

- [multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_ecommerce_implementation_plan.md) for the full project context
- [multi_vendor_database_architecture.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_database_architecture.md) for the complete database architecture

Important rule:

- do not duplicate complex database design here beyond what is needed to explain backend behavior

## 2. Repository Scope

This repository is for backend development only.

In scope:

- Laravel application code
- API design and implementation
- authentication and authorization
- backend business workflows
- service classes and actions
- policies and gates
- form requests and API resources
- queue jobs and scheduler jobs
- eSewa payment integration
- file upload handling
- notifications and mail
- audit/logging hooks
- testing
- Docker-based backend development and deployment shape
- backend documentation

Out of scope:

- Nuxt frontend code
- frontend state management
- frontend UI implementation
- frontend SEO rendering

Frontend requirements still matter when defining API contracts, request payloads, and response shapes.

## 3. Current Runtime and Infrastructure

### 3.1 Existing Docker Services

- `app`
- `web`
- `queue`
- `scheduler`
- `db`
- `redis`
- `pma` optional via profile

### 3.2 Existing Runtime Characteristics

- PHP base image is `php:8.3-fpm-bookworm`
- MySQL version is `8.4`
- Redis version is `7-alpine`
- nginx serves Laravel
- queue and scheduler already run as separate services
- Traefik labels are already included for domain-based local routing

### 3.3 Existing Environment Assumptions

- `APP_URL=http://multi-vendor.localhost`
- `DB_HOST=db`
- `DB_DATABASE=multi_vendor_ecommerce`
- `DB_TEST_DATABASE=multi_vendor_ecommerce_test`
- `REDIS_HOST=redis`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `ESEWA_MODE=sandbox`
- `ESEWA_BASE_URL=https://rc-epay.esewa.com.np`
- `ESEWA_SUCCESS_URL=http://multi-vendor.localhost/api/payments/esewa/success`
- `ESEWA_FAILURE_URL=http://multi-vendor.localhost/api/payments/esewa/failure`

Implementation rules:

- Laravel app must live in the repository root
- all Laravel config must use Docker service names such as `db` and `redis`
- queue and scheduler must be treated as first-class runtime services from the start
- Docker is the default development environment

## 4. Backend Stack

- Laravel 13 or latest stable compatible with PHP 8.3
- PHP 8.3
- MySQL 8.4
- Redis 7
- Laravel Sanctum
- Laravel Queue
- Laravel Scheduler
- Laravel Policies and Gates
- Laravel Form Requests
- Laravel Notifications and Mail
- Laravel API Resources
- Laravel Events and Listeners
- PHPUnit or Pest
- Laravel Boost

### 4.1 Laravel Boost

Laravel Boost should be installed after Laravel initialization.

Use it for:

- Laravel-aware scaffolding
- generation of models, migrations, controllers, requests, policies, jobs, and tests
- reducing structural inconsistency during agent-driven implementation

Rule:

- Laravel Boost accelerates implementation, but this file remains the backend architecture source of truth

## 5. Backend Goals

The backend must support:

- customer, vendor, and admin authentication
- role-based authorization
- vendor registration and approval
- category and product management
- product browsing/search APIs
- cart management
- multi-vendor checkout
- eSewa payment initiation and verification
- top-level and vendor-specific order management
- inventory safety
- commission calculation and persistence
- customer invoices
- admin and vendor reporting APIs
- recommendation generation and exposure
- notifications, logging, and operational visibility

## 6. Cross-Cutting Implementation Rules

- keep controllers thin
- use Form Requests for validation
- use Policies and Gates for authorization
- use API Resources for public response shape
- place business workflows in Services or Actions
- use DB transactions for checkout, payment finalization, and stock updates
- JSON responses only
- paginate list endpoints
- never trust payment redirects without backend verification
- payment finalization must be idempotent
- deduct stock only after verified payment success
- snapshot product name, SKU, and price into order items
- store commission and vendor net amounts at order time
- unapproved vendors cannot sell
- vendor users may only access vendor-owned resources
- admin corrective actions should be logged

## 7. Roles and Access Model

### 7.1 Roles

- `customer`
- `vendor`
- `admin`

### 7.2 High-Level Access Rules

- customers cannot access vendor or admin endpoints
- vendors cannot access admin endpoints
- admins can manage all backend resources
- unapproved vendors may register and edit profile data, but cannot sell or activate products for sale

### 7.3 Suggested Middleware Layers

- `auth:sanctum`
- role middleware for `customer`, `vendor`, and `admin`
- verified email middleware where needed
- throttling for auth and payment endpoints

## 8. API Conventions

- version routes under `/api`
- use route groups by area
- use plural nouns for collection resources where reasonable
- return validation errors in standard Laravel JSON form
- return consistent resource payloads for list and detail endpoints
- use dedicated endpoints for workflow operations such as approval, rejection, status update, initiate payment, and verify payment

Suggested route groups:

- `/api/auth`
- `/api/admin`
- `/api/vendor`
- `/api/customer`
- `/api/categories`
- `/api/products`
- `/api/vendors`
- `/api/search`
- `/api/cart`
- `/api/checkout`
- `/api/payments`
- `/api/orders`
- `/api/recommendations`

## 9. Backend Module Map

### 9.1 Auth Module

Responsibilities:

- customer registration
- vendor registration
- login and logout
- password reset
- email verification
- current user profile bootstrap
- token lifecycle through Sanctum

### 9.2 User and Profile Module

Responsibilities:

- customer profile read and update
- customer address book management
- vendor profile read and update
- vendor store details and media paths

### 9.3 Vendor Management Module

Responsibilities:

- vendor application flow
- approval and rejection flow
- vendor suspension support
- vendor listing for admins
- vendor-only data visibility constraints

### 9.4 Category Module

Responsibilities:

- category tree management
- active category exposure for public catalog
- admin-only category create, update, and delete

### 9.5 Product Module

Responsibilities:

- vendor product CRUD
- admin moderation and disabling
- SKU and slug generation
- image attachment handling
- stock quantity persistence
- product publication rules

### 9.6 Catalog and Search Module

Responsibilities:

- public category browsing
- public product listing
- product detail
- vendor store detail
- search and filtering
- sorting and pagination
- featured and recommendation feeds

### 9.7 Cart Module

Responsibilities:

- fetch cart
- add item
- update quantity
- remove item
- recalculate cart totals from authoritative product data

### 9.8 Checkout Module

Responsibilities:

- validate customer state
- validate address
- re-fetch product prices and stock
- calculate totals
- calculate commissions
- create order, vendor orders, order items, and pending payment record

### 9.9 Payment Module

Responsibilities:

- eSewa initiation
- redirect payload generation
- success and failure handling
- backend verification
- idempotent payment finalization
- payment reconciliation support

### 9.10 Order Module

Responsibilities:

- top-level order lifecycle
- vendor-specific order segregation
- order status history
- customer order history
- vendor order processing
- admin order oversight
- invoice data exposure

### 9.11 Inventory Module

Responsibilities:

- stock validation at checkout
- safe stock deduction after verified payment
- row locking during critical stock updates
- low-stock reporting support

### 9.12 Commission Module

Responsibilities:

- global commission configuration
- vendor-specific override support
- order-time commission calculation
- vendor net amount calculation

### 9.13 Reporting Module

Responsibilities:

- admin metrics
- vendor metrics
- payment summaries
- inventory summary endpoints

### 9.14 Recommendation Module

Responsibilities:

- use completed paid order history as transaction baskets
- generate Apriori or association-rule outputs
- store recommendations
- expose recommendation API

### 9.15 Notification Module

Responsibilities:

- approval and rejection notifications
- order and payment notifications
- operational mail or in-app notification hooks

### 9.16 File Upload Module

Responsibilities:

- vendor logo and banner paths
- product thumbnail and gallery images
- invoice or document file path handling
- Laravel Filesystem abstraction for public and private storage

### 9.17 Audit and Logging Module

Responsibilities:

- payment event logging
- order event logging
- admin override visibility
- optional audit log persistence for sensitive changes

## 10. Route Inventory

The following route list should exist unless implementation constraints require small naming adjustments.

### 10.1 Auth Routes

- `POST /api/auth/register/customer`
- `POST /api/auth/register/vendor`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `GET /api/auth/me`
- `POST /api/auth/email/verification-notification`
- `GET /api/auth/verify-email/{id}/{hash}`

### 10.2 Public Catalog Routes

- `GET /api/categories`
- `GET /api/categories/{slug}`
- `GET /api/products`
- `GET /api/products/{slug}`
- `GET /api/vendors`
- `GET /api/vendors/{slug}`
- `GET /api/search`
- `GET /api/recommendations/{productId}`

### 10.3 Customer Routes

- `GET /api/customer/profile`
- `PUT /api/customer/profile`
- `GET /api/customer/addresses`
- `POST /api/customer/addresses`
- `PUT /api/customer/addresses/{id}`
- `DELETE /api/customer/addresses/{id}`
- `GET /api/cart`
- `POST /api/cart/items`
- `PUT /api/cart/items/{id}`
- `DELETE /api/cart/items/{id}`
- `POST /api/checkout`
- `GET /api/orders`
- `GET /api/orders/{orderNumber}`
- `GET /api/orders/{orderNumber}/invoice`

### 10.4 Payment Routes

- `POST /api/payments/esewa/initiate`
- `GET /api/payments/esewa/success`
- `GET /api/payments/esewa/failure`
- `POST /api/payments/esewa/verify`
- `GET /api/payments/{orderNumber}/status`

### 10.5 Vendor Routes

- `GET /api/vendor/dashboard`
- `GET /api/vendor/profile`
- `PUT /api/vendor/profile`
- `GET /api/vendor/products`
- `POST /api/vendor/products`
- `GET /api/vendor/products/{id}`
- `PUT /api/vendor/products/{id}`
- `DELETE /api/vendor/products/{id}`
- `GET /api/vendor/inventory`
- `GET /api/vendor/orders`
- `GET /api/vendor/orders/{id}`
- `PUT /api/vendor/orders/{id}/status`
- `GET /api/vendor/reports/sales`

### 10.6 Admin Routes

- `GET /api/admin/dashboard`
- `GET /api/admin/vendors`
- `PUT /api/admin/vendors/{id}/approve`
- `PUT /api/admin/vendors/{id}/reject`
- `PUT /api/admin/vendors/{id}/suspend`
- `GET /api/admin/users`
- `GET /api/admin/products`
- `PUT /api/admin/products/{id}/status`
- `GET /api/admin/orders`
- `GET /api/admin/orders/{id}`
- `PUT /api/admin/orders/{id}/status`
- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `PUT /api/admin/categories/{id}`
- `DELETE /api/admin/categories/{id}`
- `GET /api/admin/commissions`
- `PUT /api/admin/commissions`
- `GET /api/admin/reports`
- `GET /api/admin/settings`
- `PUT /api/admin/settings`

## 11. Response Shape Rules

Implementation guidance:

- use API Resources for entities exposed publicly or across multiple endpoints
- use paginated resource collections for list endpoints
- include IDs and workflow status fields needed by the frontend
- do not leak internal-only fields such as raw payment verification payloads unless explicitly required for admin diagnostics
- use consistent naming for status fields
- include enough order and vendor order summary data for dashboard views

Suggested resource classes:

- `UserResource`
- `CustomerProfileResource`
- `VendorProfileResource`
- `CategoryResource`
- `ProductResource`
- `CartResource`
- `OrderResource`
- `OrderDetailResource`
- `VendorOrderResource`
- `PaymentStatusResource`
- `RecommendationResource`
- `AdminDashboardResource`
- `VendorDashboardResource`

## 12. Laravel Structure Recommendation

Recommended structure after initialization:

```text
app/
  Actions/
  DTOs/
  Enums/
  Events/
  Exceptions/
  Http/
    Controllers/
      Api/
    Middleware/
    Requests/
    Resources/
  Jobs/
  Listeners/
  Mail/
  Models/
  Notifications/
  Policies/
  Repositories/
  Services/
  Support/
database/
  factories/
  migrations/
  seeders/
routes/
  api.php
  web.php
```

Rules:

- keep controllers thin
- place orchestration logic in services
- place smaller reusable operations in actions
- use repositories only where query complexity justifies them
- use enums for statuses when possible
- isolate payment-gateway logic in a dedicated service and config

## 13. Recommended Controllers

Suggested controllers:

- `AuthController`
- `EmailVerificationController`
- `CustomerProfileController`
- `CustomerAddressController`
- `VendorProfileController`
- `AdminVendorApprovalController`
- `AdminUserController`
- `AdminCategoryController`
- `AdminProductModerationController`
- `AdminOrderController`
- `AdminCommissionController`
- `AdminSettingsController`
- `AdminReportController`
- `CategoryController`
- `ProductController`
- `VendorStoreController`
- `SearchController`
- `CartController`
- `CheckoutController`
- `EsewaPaymentController`
- `OrderController`
- `VendorOrderController`
- `VendorProductController`
- `VendorInventoryController`
- `VendorDashboardController`
- `VendorReportController`
- `RecommendationController`

## 14. Recommended Form Requests

Suggested request classes:

- `RegisterCustomerRequest`
- `RegisterVendorRequest`
- `LoginRequest`
- `ForgotPasswordRequest`
- `ResetPasswordRequest`
- `UpdateCustomerProfileRequest`
- `StoreAddressRequest`
- `UpdateAddressRequest`
- `UpdateVendorProfileRequest`
- `ApproveVendorRequest`
- `RejectVendorRequest`
- `SuspendVendorRequest`
- `StoreCategoryRequest`
- `UpdateCategoryRequest`
- `StoreProductRequest`
- `UpdateProductRequest`
- `UpdateProductStatusRequest`
- `AddCartItemRequest`
- `UpdateCartItemRequest`
- `CheckoutRequest`
- `InitiateEsewaPaymentRequest`
- `VerifyEsewaPaymentRequest`
- `UpdateVendorOrderStatusRequest`
- `UpdateAdminOrderStatusRequest`
- `UpdateCommissionSettingsRequest`
- `UpdatePlatformSettingsRequest`

## 15. Recommended Policies and Gates

Policies should exist for:

- `ProductPolicy`
- `VendorProfilePolicy`
- `OrderPolicy`
- `VendorOrderPolicy`
- `AddressPolicy`
- `CategoryPolicy` if category editing is handled through policy checks

Gate examples:

- admin-only category management
- admin-only commission management
- admin-only settings management
- vendor-only dashboard access

Authorization rules:

- vendors may only view or mutate their own products
- vendors may only view or mutate their own vendor orders
- customers may only view or mutate their own profile, addresses, cart, and orders
- admins may manage all resources

## 16. Recommended Service Classes

Create service classes to keep controllers thin.

Core services:

- `AuthService`
- `CustomerProfileService`
- `VendorProfileService`
- `VendorApprovalService`
- `CategoryService`
- `ProductService`
- `CatalogService`
- `SearchService`
- `CartService`
- `CheckoutService`
- `InventoryService`
- `EsewaPaymentService`
- `PaymentFinalizationService`
- `OrderService`
- `InvoiceService`
- `CommissionService`
- `PlatformSettingsService`
- `ReportService`
- `RecommendationService`
- `FileUploadService`
- `AuditLogService`
- `NotificationService`

## 17. Recommended Actions

Actions should be used for smaller workflow units that may be reused across services.

Suggested actions:

- `GenerateProductSlugAction`
- `GenerateProductSkuAction`
- `GenerateOrderNumberAction`
- `ResolveCommissionRateAction`
- `CalculateCartTotalsAction`
- `CreateOrderFromCartAction`
- `SplitOrderByVendorAction`
- `SnapshotOrderItemDataAction`
- `LockProductsForCheckoutAction`
- `DeductInventoryAction`
- `RestoreInventoryAction`
- `FinalizeEsewaPaymentAction`
- `RecordOrderStatusHistoryAction`
- `GenerateInvoiceDataAction`
- `StoreProductImagesAction`
- `DeleteProductImagesAction`
- `GenerateRecommendationsAction`

## 18. Recommended Events and Listeners

Events:

- `VendorRegistered`
- `VendorApproved`
- `VendorRejected`
- `OrderPlaced`
- `PaymentInitiated`
- `PaymentVerified`
- `PaymentFailed`
- `OrderPaid`
- `VendorOrderStatusUpdated`

Listeners:

- send approval and rejection notifications
- send order confirmation notifications
- send payment status notifications
- enqueue recommendation refresh if appropriate
- write operational logs or audit entries

## 19. Auth and Account Workflow

### Features

- customer registration, login, and logout
- vendor registration, login, and logout
- admin login and logout
- password reset via email
- email verification
- role-based authorization
- optional account lockout or rate limiting after failed attempts

### Implementation Rules

- use Laravel Sanctum for SPA authentication
- create customer profile on customer registration
- create vendor profile on vendor registration
- vendor approval status defaults to `pending`
- admins should exist through seeding or administrative setup, not public registration

### Acceptance Criteria

- customer cannot access vendor or admin endpoints
- vendor cannot access admin endpoints
- admin can manage all resources
- unapproved vendor cannot sell

## 20. Vendor Registration and Approval Workflow

### Features

- vendor registration form
- business or store details
- optional legal or verification docs
- admin approval or rejection flow
- approval email or notification
- suspension support

### Required Vendor Fields

- store name
- owner name
- email
- phone
- address
- PAN or VAT optional for academic scope
- bank or eSewa settlement account info placeholder
- description
- logo
- banner

### States

- `pending`
- `approved`
- `rejected`
- `suspended`

### Rules

- rejected and suspended vendors cannot sell
- approval and rejection actions should record actor and reason where applicable
- vendor profile update does not equal approval

## 21. Category and Product Workflow

### Category Features

- hierarchical categories
- admin create, update, delete
- active or inactive visibility support
- sort order support

### Product Features

- vendor create, update, delete or archive
- admin moderate or disable product
- category assignment
- product image upload
- stock quantity updates
- SEO metadata fields

### Product Attributes to Support

- name
- slug
- SKU
- short description
- full description
- category or subcategory
- brand optional
- price
- discount price
- stock quantity
- weight or dimensions optional
- vendor ID
- status such as `draft`, `active`, `inactive`, `out_of_stock`
- thumbnail and gallery images
- SEO title and meta description

### Product Rules

- only approved vendors can publish products for sale
- SKU and slug generation must be deterministic and collision-safe
- product status changes by admin must be auditable
- soft delete or archive behavior is acceptable if implemented consistently

## 22. Public Catalog and Search Workflow

### Features

- public category list and detail
- public product list and detail
- vendor store list and detail
- keyword search
- filtering by category, price, rating, and vendor if rating support exists later
- sorting by newest, price low-high, price high-low, and popularity

### Backend Rules

- only active and sellable products should appear in public results
- category and vendor endpoints should support pagination where listing is involved
- search implementation can start with SQL filtering and evolve later
- Laravel Scout or advanced indexing is optional and should not block MVP implementation

## 23. Cart Workflow

### Features

- add item
- remove item
- update quantity
- fetch current cart

### Rules

- checkout requires authenticated customer
- cart may contain items from multiple vendors
- cart totals should be recalculated from authoritative product data, not trusted from the client
- adding unavailable or inactive products should fail validation

## 24. Checkout Workflow

### Required Flow

1. Validate customer and cart
2. Validate shipping address
3. Re-fetch product prices and stock from DB
4. Calculate totals and commissions
5. Create order in `pending_payment`
6. Create vendor orders and order items
7. Create payment record in `initiated`
8. Generate eSewa payment request
9. Return redirect or payload details needed by the frontend

### Rules

- a single cart may contain products from multiple vendors
- internal order records must be split by vendor
- each vendor must only see their own items
- checkout totals must be authoritative on the backend
- checkout must use a DB transaction

## 25. Payment Workflow

### Features

- redirect customer to eSewa sandbox
- handle success redirect
- handle failure or cancel redirect
- verify payment server-side
- store transaction record
- mark order as paid only after verified success

### Recommended Flow

1. Customer initiates payment through backend API
2. Backend creates payment intent or record
3. Backend generates `transaction_uuid`
4. Backend stores `order_id`, `amount`, and `status = initiated`
5. Backend prepares eSewa payload
6. Frontend redirects or posts to eSewa
7. eSewa redirects back to success or failure URL
8. Backend verifies transaction with eSewa
9. If verified, finalize payment and advance order state
10. If not verified, mark failed or pending review

### Mandatory Rules

- never trust frontend redirect alone
- backend verification is mandatory
- save gateway reference IDs and raw verification response
- support retry or status-check flow when callback is delayed
- prevent duplicate payment confirmation with idempotency checks
- match verified amount against order amount
- isolate eSewa logic in a dedicated service class and config

## 26. Payment Finalization Workflow

### Required Finalization Steps

1. Load payment and order inside a transaction
2. Check if payment has already been finalized
3. Verify gateway status and amount
4. Mark payment `paid` only after successful verification
5. Deduct inventory safely
6. Mark order and vendor orders appropriately
7. Create status history entries
8. Dispatch notifications

### Rules

- finalization must be idempotent
- stock deduction occurs only after verified payment success
- failed verification must not create paid orders
- duplicate callbacks must not duplicate side effects

## 27. Order and Fulfillment Workflow

### Order Features

- maintain top-level order and order item records
- vendor-wise segregation of order items
- customer order history
- vendor order processing page
- admin order oversight
- timeline history per order

### Top-Level Order Statuses

- `pending_payment`
- `payment_initiated`
- `paid`
- `processing`
- `partially_shipped`
- `completed`
- `cancelled`
- `refunded` optional
- `failed`

### Vendor Order Statuses

- `new`
- `accepted`
- `packed`
- `shipped`
- `out_for_delivery` optional
- `delivered`
- `cancelled`
- `returned` optional

### Rules

- vendor sees only `vendor_orders` and `order_items` belonging to that vendor
- admin can update or correct order status if operationally required
- corrective admin action should be logged

## 28. Inventory Workflow

### Rules

- stock decreases only after confirmed successful payment
- use DB transactions and row locking to prevent overselling
- reject checkout if requested quantity is greater than available quantity
- stock may be restored on cancelled or unpaid-expired orders if reservation logic is implemented
- admin and vendor should be able to inspect low-stock products

Optional enhancements:

- low-stock alerts
- out-of-stock badges
- inventory change history

## 29. Invoice Workflow

### Features

- customer invoice PDF or HTML print page
- vendor sales invoice or statement support
- order totals, tax, shipping, and payment status

### Required Invoice Contents

- order number
- customer details
- shipping address
- vendor and item details
- unit price, quantity, and subtotal
- discount, tax, and shipping
- total amount
- transaction status and reference

## 30. Commission Workflow

### Features

- admin sets commission percentage globally
- vendor-specific commission override optional
- per-order commission calculation
- vendor net earnings reporting

### Formula

```text
platform_commission = item_subtotal * commission_rate
vendor_net = item_subtotal - platform_commission
```

### Rules

- store both computed values permanently at order time
- do not recalculate historical financial records from future settings

## 31. Reporting Workflow

### Admin Reports

- total users
- total vendors
- approved versus pending vendors
- total orders
- revenue
- commission earned
- top products
- top vendors
- payment success and failure counts

### Vendor Reports

- orders count
- gross sales
- net earnings
- best-selling products
- recent orders
- inventory summary

### Rules

- report queries should be optimized and may use aggregate queries
- heavy reports can be moved to queued generation if needed later

## 32. Recommendation Workflow

### Features

- use completed paid order history as transaction baskets
- generate frequent itemsets or association rules
- store product recommendations
- expose recommendation API

### Practical Scope

1. Build order history dataset structure first
2. Add offline command or job to generate associations
3. Store recommendations
4. Expose related product API

### Rules

- recommendation module must not block the main commerce workflow
- it is an enhancement layer built on top of confirmed order data

## 33. Notifications and Communication

Queue-backed notifications should cover:

- vendor approval
- vendor rejection
- order placed
- payment verified
- order status changed
- password reset
- email verification

Implementation guidance:

- send user-facing notifications asynchronously where practical
- keep immediate checkout and payment flows focused on correctness first

## 34. File Upload Handling

Support backend handling for:

- vendor logo uploads
- vendor banner uploads
- product thumbnail uploads
- product gallery uploads

Rules:

- store only file paths in the database
- validate file type and size
- use Laravel Filesystem for all uploaded and generated files
- store public media in `storage/app/public/`
- expose public media through the `public/storage` symbolic link
- store private files such as invoices and protected documents in `storage/app/private/`
- serve private files only through authorized controller actions
- keep business logic disk-agnostic so the same storage contract can later target S3-compatible storage

## 35. Queue and Scheduler

Queue jobs should cover:

- notifications and emails
- recommendation generation
- non-blocking heavy tasks
- optional reconciliation or reporting jobs

Scheduled jobs should cover:

- recommendation refresh
- cleanup of stale pending payments
- payment reconciliation checks if needed

This design fits the current Compose services `queue` and `scheduler`.

## 36. Security Requirements

- use Laravel default password hashing
- validate all inputs
- apply rate limiting for auth and payment endpoints
- use role-based authorization
- keep secrets only in environment variables
- validate callbacks and external payment data
- do not expose payment secrets to the frontend
- do not trust client-supplied totals, prices, or product state

## 37. Performance and Reliability Requirements

### Performance

- query optimization and eager loading
- pagination for list endpoints
- Redis cache for common lists and settings where useful
- DB indexing per the database architecture document

### Reliability

- DB transactions around checkout, order creation, and payment verification
- centralized exception handling
- queue retry rules
- logs for payment and order events

## 38. Testing Requirements

Every meaningful backend feature should ship with tests.

Must-have test areas:

- auth flows
- email verification flows
- password reset flows
- vendor approval restrictions
- product CRUD authorization
- category management authorization
- cart validation
- checkout totals
- multi-vendor order splitting
- payment verification
- duplicate callback protection
- stock deduction safety
- vendor order visibility
- admin-only actions
- commission calculation persistence
- invoice access control
- recommendation generation command or job behavior if implemented

Testing guidance:

- use feature tests for API workflows
- use unit tests for smaller calculation or service logic
- test both authorized and unauthorized cases
- test idempotency for payment finalization

## 39. Implementation Order

1. Initialize Laravel in the repo root.
2. Confirm Docker app, web, queue, scheduler, db, and redis compatibility.
3. Install Sanctum and Laravel Boost.
4. Establish auth, roles, profile models, and base middleware.
5. Implement vendor registration and approval flow.
6. Implement categories and products.
7. Implement public catalog and search APIs.
8. Implement customer addresses and cart.
9. Implement checkout flow.
10. Implement eSewa payment flow and payment finalization.
11. Implement orders, order status history, invoices, and inventory safety.
12. Implement commissions and settings.
13. Implement reports.
14. Implement recommendations.
15. Add notifications, logging, and hardening.
16. Complete test coverage.

## 40. Codex Implementation Rules

When Codex is asked to implement backend work later, it should:

- read this backend implementation file first
- use the database architecture file for schema-level details
- keep controllers thin
- prefer service or action classes for business workflows
- use Form Requests, Policies, and API Resources
- follow the route inventory unless the user explicitly asks for a different route shape
- respect Docker hostnames and runtime assumptions
- add tests with every meaningful backend change

## 41. Codex Task Template

When creating a later Codex task for a backend feature, include:

- feature name
- target module or files
- routes to add or change
- involved tables
- validation rules
- authorization rules
- business workflow expectations
- response shape expectations
- tests required
- whether queue or scheduler integration is needed

## 42. Final Backend Summary

This file should be sufficient for later backend implementation of:

- routes
- modules
- controllers
- service classes
- actions
- policies
- requests
- resources
- queue jobs
- scheduler jobs
- business logic rules
- testing expectations

Complex database architecture should remain in the separate database document, but all backend behavior that depends on that data model is defined here clearly enough for Codex to implement the Laravel backend later.
