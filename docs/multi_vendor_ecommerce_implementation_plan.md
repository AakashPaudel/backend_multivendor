# Multi-Vendor Ecommerce System — Full Implementation Plan

## 1. Project Overview

This document is the complete implementation plan for the **Multi-Vendor Ecommerce System** proposed in the submitted project proposal. It expands the proposal into a production-style execution blueprint covering **frontend, backend, database, Docker-based development/deployment, APIs, workflows, security, testing, payment integration, recommendation module, SEO, DevOps, and documentation**.

This plan is written so the project can be implemented largely with AI-assisted coding tools such as Codex while still maintaining clear architecture, module boundaries, acceptance criteria, and delivery steps.

---

## 2. Scope of the Project

The system will provide a centralized ecommerce marketplace where:

- **Customers** can register, log in, browse products, search/filter items, add items to cart, place orders, pay online via **eSewa Sandbox**, track orders, and view order history.
- **Vendors** can register, wait for admin approval, manage products, inventory, orders, sales, and earnings.
- **Admins** can manage users, vendors, categories, products, commissions, orders, platform settings, and reports.

The plan covers all major features referenced in the proposal:

- User registration and login
- Role-based access control
- Vendor registration and admin approval
- Product listing and management
- Product browsing and search
- Shopping cart and checkout
- Secure online payment via **eSewa sandbox**
- Order placement and vendor-wise order distribution
- Order tracking and status updates
- Inventory management and stock updates
- Order history and invoice generation
- Search engine optimization
- Commission handling
- Reports and analytics
- Recommendation system using **Apriori / association rules**
- Dockerized backend development and deployment

---

## 3. Recommended Technology Stack

The proposal specifies **Nuxt.js** for frontend, **Laravel** for backend, and **MySQL** for relational data storage. The implementation below stays aligned with that proposal.

### 3.1 Frontend

- **Nuxt 4**
- Typescript for type safety
- Vue 3 + Composition API
- Pinia for state management
- Tailwind CSS for UI styling
- Nuxt UI components and custom styling with tailwind for consistent and unique design
- Vueuse for composables and utilities
- Nuxt `$fetch` and `useFetch` for API calls
- Zod or VeeValidate + Yup/Zod for form validation
- Chart.js or ECharts for dashboards
- Optional: `@nuxt/image` for optimized image rendering

### 3.2 Backend

- **Laravel 13** (or latest stable Laravel version available in your environment)
- PHP 8.3+
- Laravel Sanctum for SPA/API authentication
- Laravel Boost for Laravel-aware scaffolding and coding-agent-assisted implementation
- Laravel Queue for background jobs
- Laravel Events/Listeners for order and notification workflows
- Laravel Policies/Gates for authorization
- Laravel Form Requests for validation
- Laravel Notifications / Mail
- Laravel Scheduler for cron jobs
- Laravel Scout (optional) if you later add advanced search indexing

### 3.3 Database and Storage

- **MySQL 8**
- Redis (recommended) for queue/cache/session optimization
- Laravel Filesystem for uploaded and generated files
- public media stored at `storage/app/public/` and exposed through `public/storage`
- private files such as invoices and protected documents stored at `storage/app/private/`
- S3-compatible storage remains a future option through the same Filesystem abstraction

### 3.4 Search and SEO

- Server-side rendered product/category/vendor pages using Nuxt
- Metadata management per route
- Sitemap generation
- Robots.txt
- Structured product data (JSON-LD)
- Optional: Meilisearch later if project scope allows

### 3.5 Payment Gateway

- **eSewa ePay Sandbox** for testing transactions
- Payment verification/status check flow
- Merchant sandbox credentials stored in environment variables only

### 3.6 DevOps / Deployment

- **Docker** for backend development and deployment
- Docker Compose for backend local orchestration
- Nginx for serving Laravel in containerized environment
- Traefik-compatible routing for local/proxied domain-based access
- Optional CI/CD using GitHub Actions
- Reverse proxy + SSL in production

### 3.7 Laravel Boost and Coding-Agent Workflow

- **Laravel Boost** should be included in the backend toolchain because the project will be implemented heavily with Codex
- Use Laravel Boost to accelerate generation of Laravel-native modules such as models, migrations, controllers, requests, policies, jobs, and tests
- Laravel Boost should improve speed and consistency, but it must not override the architecture and business rules defined in this document
- When Codex is asked to implement a backend feature, this document remains the source of truth for workflows, schema, roles, and acceptance criteria

---

## 4. High-Level System Architecture

## 4.1 Architecture Style

Use a **decoupled frontend + backend architecture with separate repositories**:

- **Nuxt frontend** handles pages, SSR, user interaction, SEO, dashboards
- **Laravel backend API** handles business logic, auth, database, payments, inventory, order processing
- **MySQL** stores all transactional data
- **Redis** supports queue/cache/session (recommended)
- **Docker Compose** orchestrates backend services during development

## 4.2 Main Services

- `api` → Laravel app
- `mysql` → database
- `redis` → queue/cache
- `nginx` → reverse proxy/web server for Laravel
- `queue-worker` → background jobs
- `scheduler` → scheduled tasks

## 4.3 Current Backend Repository Docker Reality

The current backend repository already contains a concrete Docker setup. The implementation plan should follow that setup rather than only a generic example.

### Current Compose Services in This Repository

- `app` -> main PHP-FPM Laravel container built from the repository `Dockerfile`
- `web` -> nginx container exposing the application through `${APP_PORT}`
- `queue` -> dedicated queue worker container running the `queue-worker` entry command
- `scheduler` -> dedicated scheduler container running the `scheduler` entry command
- `db` -> MySQL 8.4 database container
- `redis` -> Redis 7 container
- `pma` -> optional phpMyAdmin service enabled through the `db-admin` profile

### Current Dockerfile Characteristics

- base image: `php:8.3-fpm-bookworm`
- installed PHP extensions include `bcmath`, `exif`, `gd`, `imagick`, `intl`, `opcache`, `pcntl`, `pdo_mysql`, `redis`, and `zip`
- Composer is available in the image
- custom container commands are provided through:
  - `Docker/scripts/app-entrypoint.sh`
  - `Docker/scripts/queue-worker.sh`
  - `Docker/scripts/scheduler.sh`

### Current Backend Runtime Conventions

- Laravel code will live in the repository root and be mounted into `/var/www/html`
- container-to-container hostnames must be used in Laravel configuration:
  - MySQL host -> `db`
  - Redis host -> `redis`
- queue and scheduler already exist as first-class services in local development
- the backend local domain is intended to be `http://multi-vendor.localhost`
- phpMyAdmin is available locally through the configured profile and domain variables
- Traefik labels are already present, so the setup is compatible with domain-based local routing

### Current Backend Environment Shape

From the existing `.env.example`, the backend is currently designed around:

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

### Practical Planning Implication

- Docker is the default development environment
- Laravel will be initialized into this existing containerized setup
- Redis-backed queues and sessions are part of the baseline, not an optional later enhancement
- queue jobs and scheduler tasks should be designed from the start for real container execution

## 4.3 Recommended Two-Repository Structure

```text
multi-vendor-ecommerce-frontend/
├── app.vue
├── assets/
├── components/
├── composables/
├── layouts/
├── middleware/
├── pages/
├── plugins/
├── stores/
├── types/
├── utils/
├── .env.example
└── README.md

multi-vendor-ecommerce-backend/
├── app/
├── database/
├── routes/
├── infra/
│   └── docker/
│       ├── nginx/
│       ├── php/
│       └── mysql/
├── docker-compose.yml
├── .env.example
└── README.md
```

Notes:
- Frontend and backend are versioned and deployed independently.
- Docker configuration lives only in the backend repository.
- Frontend runs locally with Node/Nuxt and connects to the backend API base URL.

---

## 5. User Roles and Responsibilities

## 5.1 Customer

- Register and log in
- Browse products
- Search and filter products
- View product details
- Add to cart / update cart
- Checkout
- Pay using eSewa sandbox
- View order confirmation
- Track order status
- View order history
- Download invoice
- Manage profile and addresses
- Leave reviews (optional but recommended)

## 5.2 Vendor

- Register as vendor
- Wait for admin approval
- Log in to vendor dashboard
- Manage vendor profile/store details
- Add/edit/delete products
- Manage inventory
- View vendor orders only
- Process orders
- Update order status
- View sales, earnings, and payout summary
- View invoices/settlement reports

## 5.3 Admin

- Log in securely
- Approve/reject vendor applications
- Manage all users
- Manage all vendors
- Manage categories/subcategories
- Moderate products if needed
- Monitor orders
- Manage platform commissions
- View reports and analytics
- Configure platform settings
- Handle payment verification anomalies
- Manage banners/content pages (optional)

---

## 6. Functional Requirements Breakdown

This section expands the proposal into implementable modules.

## 6.1 Authentication and Authorization

### Features
- Customer registration/login/logout
- Vendor registration/login/logout
- Admin login/logout
- Password reset via email
- Email verification for customers/vendors
- Role-based authorization
- Session/token management
- Optional: account lockout/rate limiting after failed attempts

### Implementation
- Laravel Sanctum for SPA authentication
- Roles: `customer`, `vendor`, `admin`
- Middleware for route protection
- Policies for resource ownership and access control

### Acceptance Criteria
- A customer cannot access vendor/admin endpoints
- A vendor cannot access admin endpoints
- Admin can manage all resources
- Unapproved vendors cannot sell

## 6.2 Vendor Registration and Approval

### Features
- Vendor registration form
- Upload business/store details
- Upload legal/verification docs if needed
- Admin approval/rejection flow
- Approval email/notification

### Key Fields
- Store name
- Owner name
- Email
- Phone
- Address
- PAN/VAT (optional for academic scope)
- Bank/eSewa settlement account info (placeholder for future payouts)
- Description / logo / banner

### States
- `pending`
- `approved`
- `rejected`
- `suspended`

## 6.3 Product Catalog Management

### Features
- Vendor creates product
- Vendor edits product
- Vendor deletes/archive product
- Admin can moderate or disable products
- Product images upload
- Product category assignment
- SKU generation
- Stock quantity management
- Price, discount price, tax/shipping fields

### Product Attributes
- Name
- Slug
- SKU
- Short description
- Full description
- Category / subcategory
- Brand (optional)
- Price
- Discount price
- Stock quantity
- Weight/dimensions (optional)
- Vendor ID
- Status (`draft`, `active`, `inactive`, `out_of_stock`)
- Thumbnail + gallery images
- SEO title/meta description

### Optional Extended Features
- Variants (size, color)
- Bulk import CSV
- Product tags

## 6.4 Product Browsing and Search

### Features
- Home page featured products
- Category pages
- Vendor store pages
- Product detail page
- Search by keyword
- Filter by category, price, rating, vendor
- Sort by newest, price low-high, price high-low, popularity

### SEO Requirements
- SSR pages for products/categories/vendors
- Clean URLs
- Dynamic meta tags
- Open Graph tags
- Sitemap

## 6.5 Cart and Checkout

### Features
- Add/remove items
- Update quantity
- Guest cart optional, logged-in cart required for checkout
- Address selection or new address creation
- Order summary
- Shipping fee and tax calculation
- Commission calculation for internal settlement
- eSewa payment method

### Important Multi-vendor Rule
A single customer cart can contain products from multiple vendors, but the system must:
- store a single top-level order if desired
- split into **vendor-specific suborders/order items** internally
- ensure each vendor sees only their own items

## 6.6 Payment Processing with eSewa Sandbox

### Features
- Redirect customer to eSewa sandbox payment page
- Handle success redirect
- Handle failure/cancel redirect
- Verify payment server-side
- Store transaction record
- Mark order as paid only after verified success

### Essential Rules
- Never trust frontend redirect alone
- Payment verification must be performed on backend
- Save transaction reference IDs and raw verification response
- Support retry/status-check flow when callback is delayed

## 6.7 Order Management

### Features
- Place order after payment initiation
- Maintain order and order item records
- Vendor-wise segregation of order items
- Order status lifecycle
- Customer order history
- Vendor order processing page
- Admin order oversight

### Recommended Order Statuses
Top-level order:
- `pending_payment`
- `payment_initiated`
- `paid`
- `processing`
- `partially_shipped`
- `completed`
- `cancelled`
- `refunded` (optional)
- `failed`

Vendor order / fulfillment status:
- `new`
- `accepted`
- `packed`
- `shipped`
- `out_for_delivery` (optional)
- `delivered`
- `cancelled`
- `returned` (optional)

## 6.8 Real-Time / Near Real-Time Order Tracking

### Features
- Customer sees latest order status
- Vendor updates shipment progress
- Admin can intervene/update when needed
- Timeline history per order

### Implementation Options
- Simple academic scope: polling-based status refresh
- Better scope: WebSocket/SSE notifications later

## 6.9 Inventory Management

### Rules
- Stock decreases only after confirmed successful order/payment
- Use DB transactions and row locking to prevent overselling
- Stock returns on cancelled/unpaid-expired orders if reserved
- Admin/vendor can view low stock products

### Optional Enhancements
- Low-stock alerts
- Out-of-stock badges
- Inventory change history

## 6.10 Invoice Generation

### Features
- Customer invoice PDF or HTML print page
- Vendor sales invoice/statement
- Order totals, tax, shipping, payment status

### Recommended Contents
- Order number
- Customer details
- Shipping address
- Vendor/item details
- Unit price, quantity, subtotal
- Discount/tax/shipping
- Total amount
- Transaction status/reference

## 6.11 Commission Management

### Features
- Admin sets commission percentage globally
- Optional vendor-specific commission override
- Per-order commission calculation
- Vendor net earnings reporting

### Formula
```text
platform_commission = item_subtotal * commission_rate
vendor_net = item_subtotal - platform_commission
```

Store both computed values permanently at order time to preserve financial history.

## 6.12 Reports and Analytics

### Admin Reports
- Total users
- Total vendors
- Approved vs pending vendors
- Total orders
- Revenue
- Commission earned
- Top products
- Top vendors
- Payment success/failure counts

### Vendor Reports
- Orders count
- Gross sales
- Net earnings
- Best-selling products
- Recent orders
- Inventory summary

## 6.13 Recommendation System (Apriori)

The proposal mentions **Association Rule Mining / Apriori**.

### Academic Implementation Strategy
- Use completed order history as transaction baskets
- Periodically generate frequent itemsets
- Create product association rules such as:
  - “Users who bought Product A also bought Product B”
- Show recommendations on product detail page and cart page

### Practical Scope
For final year project delivery, implement in phases:
1. Build order history dataset structure first
2. Add offline script/command to generate associations
3. Store recommendations in a `product_recommendations` table
4. Show related products in frontend

### Important Note
Do not block the main shopping system on this module. It should be a **separate, optional enhancement layer** built on top of confirmed order data.

---

## 7. Non-Functional Requirements and Implementation

## 7.1 Scalability

- Modular backend structure
- Proper indexing on database tables
- Paginated APIs
- Queue heavy jobs (emails, reports, recommendation generation)
- Containerized services for easy scaling

## 7.2 Security

- Password hashing with Laravel default secure hashing
- CSRF/XSS/SQL injection protection
- Input validation everywhere
- Rate limiting for auth and payment endpoints
- Role-based authorization
- Secrets in environment variables only
- Signed/validated callbacks where applicable

## 7.3 Performance

- SSR where needed on frontend
- Query optimization and eager loading
- Redis cache for common lists/settings
- Image optimization
- DB indexing

## 7.4 Reliability

- DB transactions around checkout/order creation/payment verification
- Centralized exception handling
- Queue retry rules
- Health checks for containers
- Logs for payment and order events

## 7.5 Usability

- Simple UI with consistent navigation
- Vendor dashboard clarity
- Mobile-responsive frontend
- Proper empty states and error messages

## 7.6 Maintainability

- Clean module separation
- Service classes for business logic
- API documentation
- Coding standards and linters
- Unit and feature tests

---

## 8. Detailed Module List

## 8.1 Frontend Modules (Nuxt)

### Public Pages
- Home page
- Product listing page
- Product detail page
- Category page
- Vendor store page
- Search results page
- About / Contact / FAQ pages (optional)

### Auth Pages
- Customer login/register
- Vendor login/register/apply
- Forgot/reset password

### Customer Pages
- Dashboard
- Profile
- Address book
- Cart
- Checkout
- Payment redirect/response pages
- Order history
- Order detail / tracking
- Invoice page
- Wishlist (optional)

### Vendor Dashboard
- Dashboard summary
- Store profile settings
- Product list
- Add/edit product page
- Inventory page
- Orders list
- Order detail page
- Sales and earnings page
- Reports page

### Admin Dashboard
- Dashboard summary
- Vendor approvals
- Users management
- Categories management
- Products management
- Orders management
- Commission settings
- Reports and analytics
- Platform settings

## 8.2 Backend Modules (Laravel)

- Auth module
- User management module
- Vendor management module
- Product management module
- Category management module
- Cart/checkout module
- Payment integration module
- Order management module
- Inventory management module
- Commission engine
- Reporting module
- Recommendation module
- Notification module
- File upload module
- Audit/logging module

---

## 9. Database Design Plan

Below is the recommended schema. You can simplify slightly if time is limited, but these entities cover the proposal completely.

## 9.1 Core Tables

### users
- id
- name
- email
- phone
- password
- role (`customer`, `vendor`, `admin`)
- email_verified_at
- status (`active`, `inactive`, `suspended`)
- created_at
- updated_at

### vendor_profiles
- id
- user_id
- store_name
- slug
- description
- logo_path (relative Laravel Filesystem path on the public disk)
- banner_path (relative Laravel Filesystem path on the public disk)
- business_email
- business_phone
- address_line
- city
- district
- country
- approval_status (`pending`, `approved`, `rejected`, `suspended`)
- approved_by
- approved_at
- rejection_reason
- commission_rate_override (nullable)
- created_at
- updated_at

### customer_profiles
- id
- user_id
- default_address_id (nullable)
- created_at
- updated_at

### addresses
- id
- user_id
- full_name
- phone
- address_line_1
- address_line_2
- city
- district
- province
- postal_code
- country
- is_default
- created_at
- updated_at

### categories
- id
- parent_id (nullable)
- name
- slug
- description
- image_path
- is_active
- sort_order
- created_at
- updated_at

### products
- id
- vendor_id
- category_id
- name
- slug
- sku
- short_description
- description
- price
- discount_price
- stock_quantity
- status
- thumbnail_path (relative Laravel Filesystem path on the public disk)
- weight
- meta_title
- meta_description
- created_at
- updated_at

### product_images
- id
- product_id
- image_path (relative Laravel Filesystem path on the public disk)
- sort_order
- created_at
- updated_at

### carts
- id
- user_id
- created_at
- updated_at

### cart_items
- id
- cart_id
- product_id
- vendor_id
- quantity
- unit_price
- created_at
- updated_at

### orders
- id
- order_number
- user_id
- address_id
- subtotal
- discount_total
- shipping_total
- tax_total
- grand_total
- payment_status
- order_status
- notes
- placed_at
- created_at
- updated_at

### vendor_orders
- id
- order_id
- vendor_id
- subtotal
- commission_amount
- net_amount
- status
- created_at
- updated_at

### order_items
- id
- order_id
- vendor_order_id
- product_id
- vendor_id
- product_name_snapshot
- sku_snapshot
- unit_price
- quantity
- line_total
- commission_amount
- net_amount
- status
- created_at
- updated_at

### payments
- id
- order_id
- payment_method
- gateway (`esewa`)
- amount
- transaction_uuid
- gateway_reference
- status
- verification_status
- raw_request_json
- raw_response_json
- paid_at
- created_at
- updated_at

### order_status_histories
- id
- order_id
- vendor_order_id (nullable)
- status
- message
- changed_by
- created_at

### platform_settings
- id
- key
- value
- created_at
- updated_at

### commissions
- id
- scope (`global`, `vendor_specific`)
- vendor_id (nullable)
- rate
- active_from
- active_to
- created_at
- updated_at

### recommendations
- id
- product_id
- recommended_product_id
- support_value
- confidence_value
- lift_value
- generated_at

### notifications (optional custom table if needed)

### audit_logs (optional but recommended)
- id
- user_id
- action
- entity_type
- entity_id
- metadata_json
- created_at

## 9.2 Database Constraints

- Unique index on `users.email`
- Unique index on `vendor_profiles.slug`
- Unique index on `products.slug`
- Unique index on `products.sku`
- Unique index on `orders.order_number`
- Foreign keys on all relationships
- Indexes on:
  - `products.vendor_id`
  - `products.category_id`
  - `products.status`
  - `vendor_orders.vendor_id`
  - `order_items.product_id`
  - `payments.order_id`
  - `payments.transaction_uuid`

## 9.3 Inventory Safety Rules

- Product stock update must happen in a DB transaction
- Use row-level locking for stock checks
- Reject checkout if requested quantity > available quantity
- Snapshot product name/SKU/price into order items so history remains accurate even if product later changes

---

## 10. API Design Plan

Use REST APIs with versioning.

```text
/api/
```

## 10.1 Auth APIs

- `POST /auth/register/customer`
- `POST /auth/register/vendor`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/me`

## 10.2 Public Catalog APIs

- `GET /categories`
- `GET /categories/{slug}`
- `GET /products`
- `GET /products/{slug}`
- `GET /vendors`
- `GET /vendors/{slug}`
- `GET /search`
- `GET /recommendations/{productId}`

## 10.3 Customer APIs

- `GET /customer/profile`
- `PUT /customer/profile`
- `GET /customer/addresses`
- `POST /customer/addresses`
- `PUT /customer/addresses/{id}`
- `DELETE /customer/addresses/{id}`
- `GET /cart`
- `POST /cart/items`
- `PUT /cart/items/{id}`
- `DELETE /cart/items/{id}`
- `POST /checkout`
- `GET /orders`
- `GET /orders/{orderNumber}`
- `GET /orders/{orderNumber}/invoice`

## 10.4 Payment APIs

- `POST /payments/esewa/initiate`
- `GET /payments/esewa/success`
- `GET /payments/esewa/failure`
- `POST /payments/esewa/verify`
- `GET /payments/{orderNumber}/status`

## 10.5 Vendor APIs

- `GET /vendor/dashboard`
- `GET /vendor/profile`
- `PUT /vendor/profile`
- `GET /vendor/products`
- `POST /vendor/products`
- `GET /vendor/products/{id}`
- `PUT /vendor/products/{id}`
- `DELETE /vendor/products/{id}`
- `GET /vendor/inventory`
- `GET /vendor/orders`
- `GET /vendor/orders/{id}`
- `PUT /vendor/orders/{id}/status`
- `GET /vendor/reports/sales`

## 10.6 Admin APIs

- `GET /admin/dashboard`
- `GET /admin/vendors`
- `PUT /admin/vendors/{id}/approve`
- `PUT /admin/vendors/{id}/reject`
- `GET /admin/users`
- `GET /admin/products`
- `PUT /admin/products/{id}/status`
- `GET /admin/orders`
- `GET /admin/categories`
- `POST /admin/categories`
- `PUT /admin/categories/{id}`
- `DELETE /admin/categories/{id}`
- `GET /admin/commissions`
- `PUT /admin/commissions`
- `GET /admin/reports`
- `GET /admin/settings`
- `PUT /admin/settings`

---

## 11. Backend Implementation Plan (Laravel)

## 11.1 Backend Folder Architecture

Recommended module-oriented structure:

```text
backend/
├── app/
│   ├── Actions/
│   ├── DTOs/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Mail/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Repositories/
│   ├── Services/
│   └── Support/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
└── routes/
    ├── api.php
    └── web.php
```

## 11.2 Core Backend Service Classes

Create service classes to keep controllers thin:

- `AuthService`
- `VendorApprovalService`
- `ProductService`
- `CartService`
- `CheckoutService`
- `InventoryService`
- `EsewaPaymentService`
- `OrderService`
- `CommissionService`
- `ReportService`
- `RecommendationService`

## 11.3 Business Logic Rules

### Checkout Transaction Flow
1. Validate customer and cart
2. Validate shipping address
3. Re-fetch product prices and stock from DB
4. Calculate totals and commissions
5. Create order in `pending_payment`
6. Create vendor orders and order items
7. Create payment record in `initiated`
8. Generate eSewa payment request
9. Redirect customer to eSewa
10. On success callback, verify server-side
11. Mark payment `paid` only after verification
12. Deduct inventory safely
13. Mark order and vendor orders appropriately
14. Create status history entries
15. Send notifications

### Vendor Order Visibility Rule
- Vendor sees only `vendor_orders` and `order_items` belonging to that vendor

### Admin Override Rule
- Admin can update or correct order status if there is operational need, but action should be logged

---

## 12. Frontend Implementation Plan (Nuxt)

## 12.1 Nuxt App Structure

```text
frontend/
├── app.vue
├── assets/
├── components/
│   ├── common/
│   ├── product/
│   ├── cart/
│   ├── checkout/
│   ├── dashboard/
│   └── layout/
├── composables/
├── layouts/
├── middleware/
├── pages/
│   ├── index.vue
│   ├── products/
│   ├── vendors/
│   ├── auth/
│   ├── customer/
│   ├── vendor/
│   └── admin/
├── plugins/
├── stores/
├── types/
└── utils/
```

## 12.2 Route Plan

### Public Routes
- `/`
- `/products`
- `/products/[slug]`
- `/categories/[slug]`
- `/vendors/[slug]`
- `/search`

### Auth Routes
- `/auth/login`
- `/auth/register`
- `/vendor/apply`
- `/auth/forgot-password`

### Customer Routes
- `/cart`
- `/checkout`
- `/customer/dashboard`
- `/customer/orders`
- `/customer/orders/[orderNumber]`
- `/customer/profile`
- `/customer/addresses`

### Vendor Routes
- `/vendor/dashboard`
- `/vendor/products`
- `/vendor/products/new`
- `/vendor/products/[id]/edit`
- `/vendor/inventory`
- `/vendor/orders`
- `/vendor/orders/[id]`
- `/vendor/reports`
- `/vendor/settings`

### Admin Routes
- `/admin/dashboard`
- `/admin/vendors`
- `/admin/products`
- `/admin/categories`
- `/admin/orders`
- `/admin/users`
- `/admin/commissions`
- `/admin/reports`
- `/admin/settings`

## 12.3 Frontend State Stores (Pinia)

- `authStore`
- `cartStore`
- `productStore`
- `checkoutStore`
- `customerOrderStore`
- `vendorStore`
- `adminStore`
- `settingsStore`

## 12.4 Frontend UX Requirements

- Responsive layout
- Loading skeletons for catalog and dashboard
- Proper form validation feedback
- Toast notifications
- Accessible inputs/buttons
- Empty states for no orders/products
- Confirmation dialogs for destructive actions

---

## 13. eSewa Sandbox Integration Plan

This is a critical part of the project.

## 13.1 Goal

Use **eSewa sandbox** during development and testing for online payments, and design backend so it can later switch to production credentials without major code changes.

## 13.2 Integration Principles

- Backend must initiate and verify payment
- Frontend only triggers payment flow
- Save all payment attempts
- Use environment variables for secret values
- Support success, failure, and verification states
- Add order/payment reconciliation logic

## 13.3 Recommended Payment Flow

### Step 1: Customer clicks Pay with eSewa
- Frontend calls Laravel API to initiate payment

### Step 2: Backend creates payment intent/record
- Generate `transaction_uuid`
- Save `order_id`, `amount`, `status = initiated`
- Prepare eSewa request payload

### Step 3: Redirect to eSewa sandbox page
- Frontend redirects or posts to the provided sandbox payment flow based on current eSewa docs

### Step 4: eSewa redirects back
- Success URL or failure URL is called

### Step 5: Backend verifies transaction
- Use backend verification/status-check process
- Confirm amount, transaction ID, and status

### Step 6: Finalize order
- If verified, mark payment as paid and advance order status
- If not verified, mark failed/pending review

## 13.4 Environment Variables

Example variables (names can differ based on your implementation):

```env
ESEWA_MODE=sandbox
ESEWA_BASE_URL=https://rc-epay.esewa.com.np
ESEWA_MERCHANT_CODE=EPAYTEST
ESEWA_SECRET_KEY=your_secret_key_here
ESEWA_SUCCESS_URL=http://localhost:8000/api/payments/esewa/success
ESEWA_FAILURE_URL=http://localhost:8000/api/payments/esewa/failure
```

## 13.5 Payment Security Requirements

- Do not expose secret keys in frontend
- Verify all callback data on server
- Match verified amount against order amount
- Prevent duplicate payment confirmation using idempotency checks
- Log raw gateway responses for debugging

## 13.6 eSewa Testing Cases

- Successful payment
- Cancelled payment
- Failed payment
- Delayed verification / timeout
- Duplicate callback
- Amount mismatch
- Invalid transaction UUID

## 13.7 Important Project Note

Because payment provider docs can evolve, keep all eSewa integration logic isolated in a dedicated service class and config file so future endpoint/request changes are easy to update.

---

## 14. Docker-Based Backend Development and Deployment Plan

The user explicitly requested that the backend be developed through Docker and deployed through Docker.

## 14.0 Current Docker Setup in This Repository

The repository already includes a working Docker-oriented foundation, so implementation should extend it rather than replace it.

### Existing Files

- `docker-compose.yml`
- `Dockerfile`
- `Docker/php/local.ini`
- `Docker/nginx/conf.d/app.conf`
- `Docker/mysql/my.cnf`
- `Docker/mysql/initdb/`
- `Docker/scripts/app-entrypoint.sh`
- `Docker/scripts/queue-worker.sh`
- `Docker/scripts/scheduler.sh`
- `Docker/policy/policy.xml`

### Existing Service Topology

- `app` handles the main Laravel PHP runtime
- `web` serves Laravel publicly through nginx
- `queue` runs background jobs
- `scheduler` runs scheduled tasks every minute
- `db` provides MySQL storage
- `redis` provides cache/queue/session infrastructure
- `pma` is optional for DB administration

### Existing Script Behaviors

- the app entrypoint prepares `storage/` and `bootstrap/cache`
- the app entrypoint attempts `php artisan storage:link` when applicable
- the queue worker waits safely if Laravel has not been initialized yet
- the scheduler exits if `artisan` is not present yet

These details matter because they reduce setup work after Laravel initialization and should be preserved.

## 14.1 Local Development Containers

Recommended services in `docker-compose.yml`:

- `api` → Laravel PHP-FPM app
- `nginx` → web server
- `mysql` → database
- `redis` → cache/queue
- `queue-worker` → Laravel queue worker
- `scheduler` → Laravel scheduler
- optional `mailpit` → email testing
- optional `phpmyadmin` → DB inspection in development only

## 14.2 Recommended Docker Compose Layout

```yaml
services:
  api:
    build:
      context: ./backend
      dockerfile: Dockerfile
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:stable-alpine
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www/html
      - ./infra/docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - api

  mysql:
    image: mysql:8
    environment:
      MYSQL_DATABASE: ecommerce
      MYSQL_USER: app
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
    ports:
      - "3307:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine

  queue-worker:
    build:
      context: ./backend
      dockerfile: Dockerfile
    command: php artisan queue:work --tries=3 --timeout=120
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - api
      - redis
      - mysql

  scheduler:
    build:
      context: ./backend
      dockerfile: Dockerfile
    command: sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - api

volumes:
  mysql_data:
```

## 14.3 Laravel Dockerfile Requirements

- Base on PHP 8.3 FPM
- Install required PHP extensions: `pdo_mysql`, `bcmath`, `mbstring`, `zip`, `gd`, `intl`, `exif`, `pcntl`
- Install Composer
- Set working directory
- Handle permissions for `storage` and `bootstrap/cache`

## 14.4 Backend Docker Development Workflow

1. Clone repository
2. Copy `.env.example` to `.env`
3. Build and start containers
4. Initialize Laravel in the repository root if not already present
5. Install composer dependencies inside container
6. Run migrations and seeders
7. Generate app key if missing
8. Test API health endpoint

Recommended practical local workflow for this repository:

1. keep the existing Docker structure as the infrastructure base
2. initialize Laravel into the repository root
3. confirm the `app`, `web`, `queue`, and `scheduler` services work with the generated Laravel app
4. install Sanctum and Laravel Boost
5. begin backend implementation module by module

## 14.5 Production Docker Deployment Requirements

- Build immutable image for backend
- Use production `.env` via secure server config
- Serve app behind reverse proxy with HTTPS
- Run migrations during deployment carefully
- Separate app, queue, and scheduler containers
- Use persistent volume for Laravel local file storage, including `storage/app/public/` and `storage/app/private/`, if storing files locally
- Back up database regularly

## 14.6 Docker Deployment Checklist

- Production env configured
- APP_DEBUG=false
- Queue worker running
- Scheduler running
- Storage linked/configured
- Logs mounted or centralized
- SSL configured
- Health endpoint exposed

---

## 15. Environment Configuration Plan

## 15.1 Backend Environment Variables

```env
APP_NAME=MultiVendorEcommerce
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=app
DB_PASSWORD=secret

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=file
REDIS_HOST=redis

SANCTUM_STATEFUL_DOMAINS=localhost:3000
FRONTEND_URL=http://localhost:3000

ESEWA_MODE=sandbox
ESEWA_BASE_URL=https://rc-epay.esewa.com.np
ESEWA_MERCHANT_CODE=EPAYTEST
ESEWA_SECRET_KEY=
ESEWA_SUCCESS_URL=http://localhost:8000/api/payments/esewa/success
ESEWA_FAILURE_URL=http://localhost:8000/api/payments/esewa/failure

MAIL_MAILER=smtp
```

## 15.2 Frontend Environment Variables

```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
NUXT_PUBLIC_APP_NAME=Multi-Vendor Ecommerce
NUXT_PUBLIC_CURRENCY=NPR
```

For production/staging with separate repositories:
- Backend `APP_URL` should point to deployed API domain.
- Backend `SANCTUM_STATEFUL_DOMAINS` and CORS origins should include deployed frontend domain.
- Frontend `NUXT_PUBLIC_API_BASE` should point to deployed backend API URL.

---

## 16. Security Implementation Checklist

## 16.1 Authentication Security

- Secure password hashing
- Password reset tokens
- Email verification
- Session invalidation on logout
- Rate limiting on login and reset endpoints

## 16.2 Authorization Security

- Role middleware
- Policies for products/orders/users
- Vendors only access own resources
- Admin-only management actions

## 16.3 Data Protection

- Validate all request inputs
- Escape frontend outputs
- Sanitize rich text if enabled
- Restrict file types for uploads
- Store uploaded paths securely as Laravel Filesystem paths rather than binary payloads

## 16.4 Payment Security

- Backend verification only
- Idempotent payment finalization
- Amount verification
- Store audit trail
- Log suspicious mismatches

## 16.5 Infrastructure Security

- Secrets only in env files / server secrets
- No credentials committed to Git
- Production HTTPS
- Container user hardening if possible
- Firewall and restricted DB access in production

---

## 17. SEO Implementation Plan

The proposal explicitly mentions advanced search engine optimization to target customers.

## 17.1 SEO Targets

- Product pages indexed
- Category pages indexed
- Vendor store pages indexed
- Fast page load
- Proper metadata

## 17.2 Nuxt SEO Tasks

- SSR for product/category/vendor routes
- Dynamic page titles
- Dynamic meta descriptions
- Open Graph tags
- Canonical URLs
- Sitemap generation
- Robots.txt
- JSON-LD structured product data

## 17.3 Content/Metadata Fields

Store per product/category:
- SEO title
- SEO description
- Slug
- Image

## 17.4 Search UX Improvements

- Search suggestions (optional)
- Filter persistence in URL query params
- Breadcrumbs
- Clear sorting/filter UI

---

## 18. Notification and Communication Plan

## 18.1 Notifications to Implement

### Customer
- Registration success
- Order confirmation
- Payment success/failure
- Order status update

### Vendor
- Vendor approval/rejection
- New order received
- Order cancellation
- Low-stock alert (optional)

### Admin
- New vendor registration pending approval
- Payment verification failure needing review

## 18.2 Channels

- In-app notifications (optional)
- Email notifications
- Dashboard alerts

Queue email sending to avoid slowing core flows.

---

## 19. Logging, Monitoring, and Auditability

## 19.1 Logs to Maintain

- Auth events
- Vendor approval events
- Product create/update/delete events
- Checkout start/fail/success
- Payment initiation/verification callbacks
- Inventory deduction/restoration
- Order status changes

## 19.2 Audit-Critical Actions

- Admin vendor approval/rejection
- Commission setting changes
- Order status overrides
- Product moderation

## 19.3 Health Monitoring

Add backend endpoints:
- `/api/health`
- `/api/ready`

Useful for Docker and deployment health checks.

---

## 20. Testing Plan

Testing must not be skipped. It is essential for a credible final year project.

## 20.1 Testing Types

### Unit Tests
- Commission calculations
- Inventory deduction logic
- Recommendation generation helpers
- Payment verification helper parsing

### Feature Tests
- Customer registration/login
- Vendor registration and approval
- Product creation/update
- Cart operations
- Checkout flow
- Payment verification flow
- Order status transitions
- Authorization restrictions

### Integration Tests
- API + DB interaction
- Queue jobs
- eSewa payment workflow with mocked responses

### Manual UI Testing
- Frontend responsiveness
- Vendor dashboard flows
- Admin approval flows
- Error handling and messages

## 20.2 Must-Have Test Scenarios

### Auth
- Register customer
- Register vendor
- Invalid login rejected
- Unapproved vendor blocked from product creation

### Product
- Vendor can create product
- Vendor cannot modify another vendor’s product
- Admin can disable product

### Checkout
- Add multiple vendor products to cart
- Checkout calculates totals correctly
- Stock insufficient blocks checkout

### Payment
- Successful eSewa flow updates order to paid
- Failed payment keeps order unpaid/failed
- Duplicate callback does not duplicate order confirmation

### Orders
- Customer sees only own orders
- Vendor sees only own vendor orders
- Admin sees all orders

### Inventory
- Stock reduces after paid order
- Stock cannot go negative

## 20.3 Test Data Seeders

Create seeders for:
- admin user
- sample customers
- sample vendors (pending + approved)
- categories
- products
- demo orders

---

## 21. Documentation Plan

You need good documentation because most implementation will be done with Codex and because final year projects are evaluated partly by clarity and completeness.

## 21.1 Essential Documentation Files

- `README.md`
- `docs/architecture.md`
- `docs/database-schema.md`
- `docs/api-spec.md`
- `docs/payment-esewa.md`
- `docs/deployment.md`
- `docs/testing.md`
- `docs/project-plan.md`
- `multi_vendor_backend_implementation.md`
- `AGENTS.md`

## 21.2 README Must Include

- Project summary
- Tech stack
- Prerequisites
- Docker setup steps (backend repo only)
- Local run instructions
- Test commands
- Seeded demo credentials
- Environment variable guide

## 21.3 API Documentation

Preferred options:
- Postman collection
- OpenAPI/Swagger spec
- Simple Markdown endpoint reference

---

## 22. Git and Branching Strategy

## 22.1 Branches

- `main` → stable production-ready
- `develop` → active integration branch
- feature branches:
  - `feature/auth`
  - `feature/vendor-module`
  - `feature/product-module`
  - `feature/checkout-payment`
  - `feature/admin-dashboard`

## 22.2 Commit Guidelines

Use clear commit messages:
- `feat: add vendor approval workflow`
- `fix: prevent duplicate payment verification`
- `test: add checkout feature tests`
- `docs: add docker deployment guide`

---

## 23. Suggested Implementation Order

This is the best practical build order for your project.

## Phase 1 — Foundation Setup

1. Finalize requirements from proposal
2. Create separate frontend and backend repositories
3. Initialize Laravel backend repository
4. Initialize Nuxt frontend repository
5. Create Docker setup for backend only
6. Configure MySQL and Redis for backend
7. Set up Git branches/workflows in both repositories
8. Prepare env files in both repositories
9. Create base README and docs in both repositories

## Phase 2 — Authentication and Roles

1. Implement user model and roles
2. Implement customer registration/login
3. Implement vendor registration/login
4. Implement admin authentication
5. Add role-based middleware and policies
6. Add password reset and email verification

## Phase 3 — Vendor and Admin Foundation

1. Create vendor profile module
2. Implement vendor application form
3. Implement admin vendor approval dashboard
4. Add vendor approval/rejection workflow
5. Block unapproved vendors from selling

## Phase 4 — Catalog Module

1. Create category module
2. Create product CRUD APIs
3. Create image upload handling
4. Build vendor product dashboard
5. Build public product list/detail pages
6. Add search, filter, sorting
7. Add SEO metadata handling

## Phase 5 — Cart and Checkout

1. Create cart and cart item tables
2. Build cart APIs
3. Build checkout summary logic
4. Add address management
5. Add total calculation service
6. Add commission calculation service

## Phase 6 — Payment Integration

1. Create payment table and statuses
2. Implement eSewa payment service
3. Implement payment initiation endpoint
4. Implement success/failure redirect handling
5. Implement backend verification/status check
6. Prevent duplicate verification
7. Add payment logs and error handling

## Phase 7 — Order and Inventory Module

1. Create order, vendor_order, order_item tables
2. Build checkout transaction workflow
3. Deduct stock on successful verification
4. Add order status history
5. Build customer order history pages
6. Build vendor order processing pages
7. Build admin order management pages

## Phase 8 — Reports, Analytics, Commission

1. Build admin reports
2. Build vendor reports
3. Build commission settings and calculations
4. Add revenue and payout summaries

## Phase 9 — Recommendation Module

1. Export completed order baskets
2. Implement Apriori generator command/script
3. Save generated product associations
4. Display “related products” on frontend

## Phase 10 — Hardening and Delivery

1. Add automated tests
2. Improve validation and error handling
3. Improve loading states and UI polish
4. Add invoices
5. Add notification emails
6. Add Docker production deployment config
7. Prepare final report/demo assets

---

## 24. Codex-Friendly Task Breakdown

Since most work will be done by Codex, structure implementation into small promptable tasks.

Important repo-level clarification for Codex:

- the overall project is full-stack, but this repository is intended for backend development only
- frontend logic in this document remains part of the overall system plan, but implementation in this repo should focus on Laravel, MySQL, Redis, Docker, queue, scheduler, and backend APIs
- all backend implementation should align with the current repository Docker setup instead of inventing a new one
- Laravel Boost should be used to speed up Laravel-native scaffolding where helpful

## 24.1 Backend Task Units

- Create migrations for auth and role system
- Create vendor profile model/migration/controller
- Add vendor approval API and policy
- Create category CRUD module
- Create product CRUD with image uploads
- Add cart module with validation
- Add checkout service using DB transactions
- Add commission calculation service
- Integrate eSewa initiation and verification service
- Add order creation and inventory deduction flow
- Add report endpoints
- Add recommendation command

## 24.2 Frontend Task Units

- Set up Nuxt auth pages and middleware
- Build vendor application page
- Build admin vendor approval page
- Build product listing/detail pages
- Build cart page with Pinia store
- Build checkout page
- Build customer orders pages
- Build vendor dashboard pages
- Build admin dashboard pages
- Add SEO metadata utilities

## 24.3 Prompting Rule for Codex

For each task, provide:
- target files
- exact module objective
- DB tables involved
- validation rules
- response format
- tests to add

This reduces rework and hallucinated code structure.

---

## 25. Example Milestones and Deliverables

## Milestone 1: Setup Complete
- Dockerized backend running
- Nuxt frontend running
- DB connected
- Auth scaffold ready
- Frontend and backend repositories linked through API configuration

## Milestone 2: Core Marketplace Ready
- Customer/vendor/admin auth
- Vendor approval
- Product CRUD
- Product listing/search

## Milestone 3: Transaction Flow Ready
- Cart and checkout
- eSewa sandbox integration
- Order creation
- Inventory deduction

## Milestone 4: Marketplace Operations Ready
- Order tracking
- Reports
- Commission management
- Invoices

## Milestone 5: Final Delivery Ready
- Recommendation module
- Tests
- Docker deployment
- Documentation and demo data

---

## 26. Suggested 10-Week Execution Plan

This aligns with the proposal’s Gantt chart but expands it into actionable work.

## Weeks 1–2: Planning and Requirement Analysis
- Review proposal thoroughly
- Freeze MVP scope
- Finalize architecture
- Define DB schema
- Set up separate repositories and backend Docker
- Create UI wireframes

## Weeks 3–4: System Design
- Design API contracts
- Build migrations and ERD
- Design frontend route structure
- Implement auth and roles
- Build vendor approval foundation

## Weeks 5–8: Coding
- Product and category modules
- Search/filter pages
- Cart and checkout
- eSewa sandbox integration
- Order/inventory/commission logic
- Vendor/admin dashboards
- Reports and invoices

## Week 9: Testing and Debugging
- Run feature tests
- Perform payment flow testing
- Fix authorization/security bugs
- Improve UX and validation
- Seed demo data

## Week 10: Deployment
- Prepare production Docker config
- Deploy backend through Docker
- Deploy frontend separately (non-Docker required)
- Connect frontend to deployed backend
- Verify final flows
- Record demo and prepare presentation

---

## 27. UI/UX Screen Checklist

## 27.1 Public / Customer Screens
- Home page
- Login page
- Registration page
- Vendor apply page
- Product listing page
- Product detail page
- Search results page
- Cart page
- Checkout page
- Payment redirect/result page
- Order history page
- Order detail/tracking page
- Profile page
- Address management page

## 27.2 Vendor Screens
- Vendor dashboard
- Store profile page
- Product list page
- Add product page
- Edit product page
- Inventory page
- Orders list page
- Order detail page
- Sales report page

## 27.3 Admin Screens
- Admin dashboard
- Vendor approvals page
- User management page
- Product management page
- Category management page
- Orders page
- Commission settings page
- Reports page
- Settings page

---

## 28. Error Handling and Edge Cases

Do not ignore these. They are essential.

## 28.1 Auth Edge Cases
- Duplicate email registration
- Vendor attempts login before approval
- Password reset for nonexistent account

## 28.2 Product Edge Cases
- Product without image
- Negative price/stock attempt
- Duplicate SKU attempt
- Product deleted while in customer cart

## 28.3 Cart/Checkout Edge Cases
- Item goes out of stock before checkout
- Price changes after item added to cart
- Mixed active/inactive products in cart
- Invalid address during checkout

## 28.4 Payment Edge Cases
- Customer closes browser after payment
- Success redirect received but verification fails
- Callback duplicated
- Timeout during verification
- Amount mismatch

## 28.5 Order Edge Cases
- One vendor cancels but others continue
- Admin updates order manually
- Vendor tries to update already-delivered order incorrectly

---

## 29. Performance Optimization Checklist

- Paginate product lists and orders
- Lazy load non-critical dashboard widgets
- Optimize product images
- Use DB indexes
- Use eager loading to avoid N+1 queries
- Cache categories/settings
- Queue email/report generation

---

## 30. Recommended Minimum Viable Product (MVP)

If time becomes limited, the MVP must still satisfy the proposal.

### MVP Must Include
- Customer, vendor, admin authentication
- Vendor registration and admin approval
- Product CRUD
- Product browsing, search, filter
- Cart and checkout
- eSewa sandbox payment
- Order creation and tracking
- Inventory updates
- Customer order history
- Vendor order management
- Admin commission and reports (basic)

### Can Be Added After MVP
- Reviews/ratings
- Wishlist
- Advanced notifications
- Product variants
- Live chat
- Full recommendation engine sophistication
- Payout automation

---

## 31. Final Project Delivery Checklist

Before final submission/demo, ensure all of the following are ready:

## 31.1 Functional Readiness
- Customer flow works end-to-end
- Vendor flow works end-to-end
- Admin flow works end-to-end
- eSewa sandbox payment works
- Order tracking works
- Inventory updates correctly
- Commission is calculated correctly

## 31.2 Technical Readiness
- Backend runs via Docker locally
- Backend deploys via Docker successfully
- DB migrations and seeders work
- Environment setup documented
- API routes documented

## 31.3 Academic Readiness
- Matches proposal objectives
- Screenshots/demo data prepared
- Gantt mapping to completed work
- Use case coverage explained
- Recommendation algorithm explained and demonstrated

## 31.4 Presentation Readiness
- Demo accounts available
- Example vendors/products seeded
- One successful eSewa sandbox payment demo path ready
- Architecture diagram prepared
- ERD prepared
- Module summary prepared

---

## 32. Recommended Demo Credentials (Local/Academic)

Create seeded users like:

- Admin: `admin@example.com`
- Vendor Approved: `vendor1@example.com`
- Vendor Pending: `vendor2@example.com`
- Customer: `customer@example.com`

Use obvious demo passwords only in local seeded development, never production.

---

## 33. Suggested Future Enhancements

These are not required for first delivery but can be mentioned in report/future scope.

- Product reviews and ratings
- Coupon/discount engine
- Wishlist
- Live shipment integration
- Automated vendor payout workflows
- Mobile app
- Full-text search engine integration (Meilisearch)
- Recommendation engine with hybrid ML methods
- Chat between customer and vendor
- Return/refund management

---

## 34. Final Recommendation

For this project, the best implementation approach is:

- **Nuxt 3** for SSR-ready frontend and dashboards
- **Laravel API** for business logic
- **MySQL** for relational consistency
- **Separate frontend and backend repositories** for clear ownership and independent deployments
- **Docker + Docker Compose** for backend development and deployment only
- **eSewa sandbox** for payment testing
- Modular architecture so Codex can generate code safely and incrementally

The project should be developed in **clear phases**, with **backend-first business logic correctness**, followed by **frontend integration**, then **payment**, **reports**, **recommendations**, **testing**, and **Docker deployment hardening**.

If implemented according to this document, the result will fully satisfy the proposal and also look much closer to a real-world marketplace system rather than a basic academic CRUD project.

---

## 35. Immediate Next Actions

Start with these in order:

1. Create separate frontend and backend repositories
2. Initialize Laravel backend and Nuxt frontend
3. Build Docker backend environment
4. Design and create database migrations
5. Implement auth + roles + vendor approval
6. Implement categories and products
7. Implement cart + checkout
8. Integrate eSewa sandbox
9. Implement orders + inventory + reports
10. Add tests, docs, and deployment configuration



