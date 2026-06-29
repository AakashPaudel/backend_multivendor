# Multi-Vendor Database Architecture

## 1. Purpose

This document contains the database architecture for the Multi-Vendor Ecommerce System.

It is derived from the existing [multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/multi_vendor_ecommerce_implementation_plan.md) and consolidates everything in that file that is directly related to the database, persistence layer, data integrity, and database-backed business workflows.

## 2. Database Stack and Storage

### Primary Database

- MySQL 8

### Supporting Data Infrastructure

- Redis for queue, cache, and session optimization
- Laravel Filesystem as the storage abstraction layer for uploaded and generated files
- public media such as product images and vendor images stored at `storage/app/public/`
- public media exposed through the `public/storage` symbolic link
- private files such as invoices and protected documents stored at `storage/app/private/`
- private files served only through authorized controller endpoints
- future S3-compatible storage remains possible without changing business logic because file access stays behind the Filesystem abstraction

## 3. High-Level Data Architecture

The system follows a decoupled frontend and backend architecture where:

- Laravel backend handles business logic, auth, database, payments, inventory, and order processing
- MySQL stores all transactional data
- Redis supports queue, cache, and sessions

## 4. Current Backend Repository Database Runtime Conventions

The current backend repository already defines concrete runtime assumptions that affect the database architecture.

### Current Services

- `app`
- `web`
- `queue`
- `scheduler`
- `db`
- `redis`
- optional `pma`

### Database Runtime Rules

- Laravel code will live in the repository root
- container-to-container hostnames must be used in Laravel configuration
- MySQL host is `db`
- Redis host is `redis`
- queue and scheduler already exist as first-class services in local development

### Current Environment Shape

- `DB_HOST=db`
- `DB_DATABASE=multi_vendor_ecommerce`
- `DB_TEST_DATABASE=multi_vendor_ecommerce_test`
- `REDIS_HOST=redis`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`

### Practical Planning Implication

- Docker is the default development environment
- Redis-backed queues and sessions are part of the baseline
- queue jobs and scheduler tasks should be designed from the start for real container execution

## 5. Core Database Design Plan

The implementation plan states that the following schema covers the proposal completely.

### 5.1 Core Tables

#### `users`

- `id`
- `name`
- `email`
- `phone`
- `password`
- `role` (`customer`, `vendor`, `admin`)
- `email_verified_at`
- `status` (`active`, `inactive`, `suspended`)
- `created_at`
- `updated_at`

#### `vendor_profiles`

- `id`
- `user_id`
- `store_name`
- `slug`
- `description`
- `logo_path` (relative Laravel Filesystem path on the public disk)
- `banner_path` (relative Laravel Filesystem path on the public disk)
- `business_email`
- `business_phone`
- `address_line`
- `city`
- `district`
- `country`
- `approval_status` (`pending`, `approved`, `rejected`, `suspended`)
- `approved_by`
- `approved_at`
- `rejection_reason`
- `commission_rate_override` (nullable)
- `created_at`
- `updated_at`

#### `customer_profiles`

- `id`
- `user_id`
- `default_address_id` (nullable)
- `created_at`
- `updated_at`

#### `addresses`

- `id`
- `user_id`
- `full_name`
- `phone`
- `address_line_1`
- `address_line_2`
- `city`
- `district`
- `province`
- `postal_code`
- `country`
- `is_default`
- `created_at`
- `updated_at`

#### `categories`

- `id`
- `parent_id` (nullable)
- `name`
- `slug`
- `description`
- `image_path`
- `is_active`
- `sort_order`
- `created_at`
- `updated_at`

#### `products`

- `id`
- `vendor_id`
- `category_id`
- `name`
- `slug`
- `sku`
- `short_description`
- `description`
- `price`
- `discount_price`
- `stock_quantity`
- `status`
- `thumbnail_path` (relative Laravel Filesystem path on the public disk)
- `weight`
- `meta_title`
- `meta_description`
- `created_at`
- `updated_at`

#### `product_images`

- `id`
- `product_id`
- `image_path` (relative Laravel Filesystem path on the public disk)
- `sort_order`
- `created_at`
- `updated_at`

#### `carts`

- `id`
- `user_id`
- `created_at`
- `updated_at`

#### `cart_items`

- `id`
- `cart_id`
- `product_id`
- `vendor_id`
- `quantity`
- `unit_price`
- `created_at`
- `updated_at`

#### `orders`

- `id`
- `order_number`
- `user_id`
- `address_id`
- `subtotal`
- `discount_total`
- `shipping_total`
- `tax_total`
- `grand_total`
- `payment_status`
- `order_status`
- `notes`
- `placed_at`
- `created_at`
- `updated_at`

#### `vendor_orders`

- `id`
- `order_id`
- `vendor_id`
- `subtotal`
- `commission_amount`
- `net_amount`
- `status`
- `created_at`
- `updated_at`

#### `order_items`

- `id`
- `order_id`
- `vendor_order_id`
- `product_id`
- `vendor_id`
- `product_name_snapshot`
- `sku_snapshot`
- `unit_price`
- `quantity`
- `line_total`
- `commission_amount`
- `net_amount`
- `status`
- `created_at`
- `updated_at`

#### `payments`

- `id`
- `order_id`
- `payment_method`
- `gateway` (`esewa`)
- `amount`
- `transaction_uuid`
- `gateway_reference`
- `status`
- `verification_status`
- `raw_request_json`
- `raw_response_json`
- `paid_at`
- `created_at`
- `updated_at`

#### `order_status_histories`

- `id`
- `order_id`
- `vendor_order_id` (nullable)
- `status`
- `message`
- `changed_by`
- `created_at`

#### `platform_settings`

- `id`
- `key`
- `value`
- `created_at`
- `updated_at`

#### `commissions`

- `id`
- `scope` (`global`, `vendor_specific`)
- `vendor_id` (nullable)
- `rate`
- `active_from`
- `active_to`
- `created_at`
- `updated_at`

#### `recommendations`

- `id`
- `product_id`
- `recommended_product_id`
- `support_value`
- `confidence_value`
- `lift_value`
- `generated_at`

### 5.2 Optional Tables

#### `notifications`

- optional custom table if needed

#### `audit_logs`

- `id`
- `user_id`
- `action`
- `entity_type`
- `entity_id`
- `metadata_json`
- `created_at`

## 6. Core Relationship Model

The database plan implies the following core relationships:

- `vendor_profiles.user_id` -> `users.id`
- `customer_profiles.user_id` -> `users.id`
- `customer_profiles.default_address_id` -> `addresses.id`
- `addresses.user_id` -> `users.id`
- `categories.parent_id` -> `categories.id`
- `products.vendor_id` -> vendor user or vendor-owned entity
- `products.category_id` -> `categories.id`
- `product_images.product_id` -> `products.id`
- `carts.user_id` -> `users.id`
- `cart_items.cart_id` -> `carts.id`
- `cart_items.product_id` -> `products.id`
- `cart_items.vendor_id` -> vendor user or vendor-owned entity
- `orders.user_id` -> `users.id`
- `orders.address_id` -> `addresses.id`
- `vendor_orders.order_id` -> `orders.id`
- `vendor_orders.vendor_id` -> vendor user or vendor-owned entity
- `order_items.order_id` -> `orders.id`
- `order_items.vendor_order_id` -> `vendor_orders.id`
- `order_items.product_id` -> `products.id`
- `order_items.vendor_id` -> vendor user or vendor-owned entity
- `payments.order_id` -> `orders.id`
- `order_status_histories.order_id` -> `orders.id`
- `order_status_histories.vendor_order_id` -> `vendor_orders.id`
- `commissions.vendor_id` -> vendor user or vendor-owned entity when scope is vendor-specific
- `recommendations.product_id` -> `products.id`
- `recommendations.recommended_product_id` -> `products.id`
- `audit_logs.user_id` -> `users.id`

Rule from the implementation plan:

- foreign keys on all relationships

## 7. Database Constraints and Indexing

### 7.1 Required Unique Constraints

- unique index on `users.email`
- unique index on `vendor_profiles.slug`
- unique index on `products.slug`
- unique index on `products.sku`
- unique index on `orders.order_number`

### 7.2 Required Foreign Keys

- foreign keys on all relationships

### 7.3 Required Indexes

- `products.vendor_id`
- `products.category_id`
- `products.status`
- `vendor_orders.vendor_id`
- `order_items.product_id`
- `payments.order_id`
- `payments.transaction_uuid`

### 7.4 Performance Guidance from the Plan

- proper indexing on database tables
- query optimization and eager loading
- DB indexing

## 8. Role and Vendor State Data Rules

### Roles

- `customer`
- `vendor`
- `admin`

### User Status

- `active`
- `inactive`
- `suspended`

### Vendor Approval States

- `pending`
- `approved`
- `rejected`
- `suspended`

Critical rule:

- unapproved vendors cannot sell

## 9. Product and Catalog Persistence Rules

The product catalog must support persistence for:

- product creation
- product editing
- product deletion or archiving
- admin product moderation or disabling
- product image uploads
- product category assignment
- SKU generation
- stock quantity management
- price, discount price, and optional tax/shipping-related data

Media persistence rules:

- product and vendor media should be stored on Laravel's public disk at `storage/app/public/`
- public media references should be persisted as relative file paths, not binary payloads
- any future protected file output should use Laravel's private disk at `storage/app/private/`
- private file access must stay behind authorized controller responses rather than direct public URLs

Product attributes defined by the implementation plan:

- name
- slug
- SKU
- short description
- full description
- category or subcategory
- brand (optional)
- price
- discount price
- stock quantity
- weight or dimensions (optional)
- vendor ID
- status (`draft`, `active`, `inactive`, `out_of_stock`)
- thumbnail and gallery images
- SEO title and meta description

Optional extended product features:

- variants such as size and color
- bulk import CSV
- product tags

## 10. Cart and Checkout Data Architecture

### Cart Rules

- a single customer cart can contain products from multiple vendors
- guest cart is optional, but logged-in cart is required for checkout
- cart supports add, remove, and quantity update operations

### Checkout Data Requirements

- address selection or new address creation
- order summary
- shipping fee and tax calculation
- commission calculation for internal settlement
- eSewa payment method

### Important Multi-Vendor Rule

A single customer cart can contain products from multiple vendors, but the system must:

- store a single top-level order if desired
- split into vendor-specific suborders or order items internally
- ensure each vendor sees only their own items

## 11. Order Data Architecture

### Order Management Requirements

- place order after payment initiation
- maintain order and order item records
- vendor-wise segregation of order items
- order status lifecycle
- customer order history
- vendor order processing page
- admin order oversight

### Recommended Top-Level Order Statuses

- `pending_payment`
- `payment_initiated`
- `paid`
- `processing`
- `partially_shipped`
- `completed`
- `cancelled`
- `refunded` (optional)
- `failed`

### Recommended Vendor Order or Fulfillment Statuses

- `new`
- `accepted`
- `packed`
- `shipped`
- `out_for_delivery` (optional)
- `delivered`
- `cancelled`
- `returned` (optional)

### Order Tracking Requirement

- timeline history per order should be persisted

## 12. Inventory Data Integrity Rules

- stock decreases only after confirmed successful order or payment
- use DB transactions and row locking to prevent overselling
- stock returns on cancelled or unpaid-expired orders if reserved
- admin and vendor can view low stock products

Optional enhancements:

- low-stock alerts
- out-of-stock badges
- inventory change history

### Mandatory Inventory Safety Rules

- product stock update must happen in a DB transaction
- use row-level locking for stock checks
- reject checkout if requested quantity is greater than available quantity
- snapshot product name, SKU, and price into order items so history remains accurate even if the product later changes

## 13. Payment Data Architecture

### Payment Processing Requirements

- redirect customer to eSewa sandbox payment page
- handle success redirect
- handle failure or cancel redirect
- verify payment server-side
- store transaction record
- mark order as paid only after verified success

### Essential Payment Rules

- never trust frontend redirect alone
- payment verification must be performed on backend
- save transaction reference IDs and raw verification response
- support retry or status-check flow when callback is delayed

### Payment Persistence Requirements

The backend must:

- save all payment attempts
- generate `transaction_uuid`
- save `order_id`, `amount`, and `status = initiated`
- support success, failure, and verification states
- add order and payment reconciliation logic

### Payment Security Requirements That Affect Persistence

- verify all callback data on server
- match verified amount against order amount
- prevent duplicate payment confirmation using idempotency checks
- log raw gateway responses for debugging

### eSewa Testing Cases with Database Impact

- successful payment
- cancelled payment
- failed payment
- delayed verification or timeout
- duplicate callback
- amount mismatch
- invalid transaction UUID

## 14. Commission Data Architecture

### Commission Features

- admin sets commission percentage globally
- optional vendor-specific commission override
- per-order commission calculation
- vendor net earnings reporting

### Formula

```text
platform_commission = item_subtotal * commission_rate
vendor_net = item_subtotal - platform_commission
```

### Persistence Rule

Store both computed values permanently at order time to preserve financial history.

## 15. Recommendation Data Architecture

The recommendation module uses Association Rule Mining / Apriori.

### Academic Implementation Strategy

- use completed order history as transaction baskets
- periodically generate frequent itemsets
- create product association rules such as "Users who bought Product A also bought Product B"

### Practical Scope

1. Build order history dataset structure first
2. Add offline script or command to generate associations
3. Store recommendations in a `product_recommendations` table
4. Show related products in frontend

### Important Note

Do not block the main shopping system on this module. It should be a separate optional enhancement layer built on top of confirmed order data.

## 16. Non-Functional Database Requirements

### Scalability

- modular backend structure
- proper indexing on database tables
- paginated APIs
- queue heavy jobs such as emails, reports, and recommendation generation
- containerized services for easy scaling

### Security

- CSRF/XSS/SQL injection protection
- input validation everywhere
- rate limiting for auth and payment endpoints
- role-based authorization
- secrets in environment variables only
- signed or validated callbacks where applicable

### Performance

- query optimization and eager loading
- Redis cache for common lists and settings
- DB indexing

### Reliability

- DB transactions around checkout, order creation, and payment verification
- centralized exception handling
- queue retry rules
- logs for payment and order events

## 17. Database-Sensitive Backend Workflow Rules

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

- vendor sees only `vendor_orders` and `order_items` belonging to that vendor

### Admin Override Rule

- admin can update or correct order status if there is operational need, but action should be logged

## 18. Queue, Scheduler, and Data Jobs

Because Redis-backed queues and scheduler tasks are part of the baseline, the database architecture must support job-driven workflows such as:

- recommendation generation
- reports
- notifications
- reconciliation logic

## 19. Laravel Database Layer Structure

The backend folder architecture in the implementation plan includes:

```text
database/
├── factories/
├── migrations/
└── seeders/
```

Related application-layer support:

```text
app/
├── Models/
├── Repositories/
└── Services/
```

## 20. Summary of Critical Database Rules

- use MySQL as the primary transactional database
- use Redis for queue, cache, and sessions
- use Docker service names `db` and `redis`
- enforce foreign keys on all relationships
- enforce uniqueness on email, vendor slug, product slug, product SKU, and order number
- support multi-vendor cart splitting into vendor-specific order records
- deduct stock only after verified payment success
- use DB transactions and row locking for checkout and inventory safety
- store payment attempts, gateway references, and raw verification payloads
- snapshot product name, SKU, and price into order items
- store commission and vendor net amounts permanently at order time
- log status history and admin overrides for operational traceability
