# Multi-Vendor Backend API Guide

This guide reflects the current Laravel backend implementation.

## Base URL

- Local API base URL: `http://multi-vendor.localhost/api`
- All API routes return JSON unless a route explicitly returns file or HTML content such as invoice delivery.

## Authentication Model

- Authentication uses Laravel Sanctum bearer tokens.
- Frontend login/register flows should store the returned token and send it as `Authorization: Bearer <token>`.
- `GET /api/auth/me` is the frontend bootstrap route for the authenticated user.
- Email verification is enabled.
- Password reset links are generated for the frontend URL configured in `APP_FRONTEND_URL`.

## Standard Request Headers

- Public JSON requests:
  - `Accept: application/json`
- Authenticated JSON requests:
  - `Accept: application/json`
  - `Authorization: Bearer <token>`
- File upload requests:
  - `Accept: application/json`
  - `Authorization: Bearer <token>`
  - `Content-Type: multipart/form-data`
  - For Laravel-backed update uploads from the frontend, prefer `POST` plus `FormData` `_method=PUT`

## Standard Error Responses

- Validation failures return `422` with:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": [
      "Validation message."
    ]
  }
}
```

- Authentication failures return `401` with:

```json
{
  "message": "Unauthenticated."
}
```

- Authorization failures return `403` with:

```json
{
  "message": "This action is unauthorized."
}
```

- Unknown API routes return `404` with:

```json
{
  "message": "Resource not found."
}
```

- Throttled routes return `429` with a JSON `message`.

## Role Model

- `admin`: marketplace controls, reporting, moderation, settings
- `vendor`: own store profile, own products, own vendor orders, own reports
- `customer`: own profile, own addresses, cart, checkout, payment verification, own orders

## Route Groups

### Auth

- `POST /api/auth/register/customer`
- `POST /api/auth/register/vendor`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/email/verification-notification`
- `GET /api/auth/verify-email/{user}/{hash}`

### Customer

- `GET /api/customer/profile`
- `PUT /api/customer/profile`
- `GET /api/customer/addresses`
- `POST /api/customer/addresses`
- `PUT /api/customer/addresses/{address}`
- `DELETE /api/customer/addresses/{address}`

### Vendor

- `GET /api/vendor/profile`
- `PUT /api/vendor/profile`
- `GET /api/vendor/dashboard`
- `GET /api/vendor/products`
- `POST /api/vendor/products`
- `GET /api/vendor/products/{product}`
- `PUT /api/vendor/products/{product}`
- `DELETE /api/vendor/products/{product}`
- `GET /api/vendor/orders`
- `GET /api/vendor/orders/{vendorOrder}`
- `PUT /api/vendor/orders/{vendorOrder}/status`
- `GET /api/vendor/reports/sales`
  Returns `filters`, `summary`, and a nested paginated `orders` payload with `data`, `links`, and `meta`.

Frontend integration note:

- vendor product/profile file-upload updates should be submitted as `POST` multipart requests with `_method=PUT` so Laravel handles method spoofing correctly

### Admin

- `GET /api/admin/dashboard`
- `GET /api/admin/reports`
- `GET /api/admin/users`
- `GET /api/admin/vendors`
- `PUT /api/admin/vendors/{vendorProfile}/approve`
- `PUT /api/admin/vendors/{vendorProfile}/reject`
- `PUT /api/admin/vendors/{vendorProfile}/suspend`
- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `PUT /api/admin/categories/{category}`
- `DELETE /api/admin/categories/{category}`
- `GET /api/admin/products`
- `PUT /api/admin/products/{product}/status`
- `GET /api/admin/orders`
- `GET /api/admin/orders/{order}`
- `PUT /api/admin/orders/{order}/status`
- `GET /api/admin/settings`
- `PUT /api/admin/settings`
- `GET /api/admin/commissions`
- `PUT /api/admin/commissions`

### Public Catalog

- `GET /api/categories`
- `GET /api/categories/{slug}`
- `GET /api/products`
- `GET /api/products/{slug}`
- `GET /api/vendors`
- `GET /api/vendors/{slug}`
- `GET /api/search`
- `GET /api/recommendations/{product}`

### Cart and Checkout

- `GET /api/cart`
- `POST /api/cart/items`
- `PUT /api/cart/items/{cartItem}`
- `DELETE /api/cart/items/{cartItem}`
- `POST /api/checkout`

### Payments

- `POST /api/payments/esewa/initiate`
- `GET /api/payments/esewa/success`
- `GET /api/payments/esewa/failure`
- `POST /api/payments/esewa/verify`
- `GET /api/payments/{orderNumber}/status`

### Orders and Invoices

- `GET /api/orders`
- `GET /api/orders/{orderNumber}`
- `GET /api/orders/{orderNumber}/invoice`

## Core Payload Notes

### Registration

- Customer registration creates `users` and `customer_profiles`.
- Vendor registration creates `users` and `vendor_profiles`.
- Vendor accounts start with `approval_status = pending`.

### Product Media

- Vendor logos, vendor banners, category images, product thumbnails, and product gallery images are stored on the Laravel `public` disk.
- Database rows store relative paths only.
- Public URLs are served through `public/storage`.

### Invoices

- Invoices are generated as private HTML files on Laravel's private `local` disk.
- Downloads are served through an authorized controller endpoint.

## Payment Integration Summary

- Checkout creates the order graph and an initiated payment attempt.
- `POST /api/payments/esewa/initiate` returns the signed payload the frontend must post to eSewa.
- The frontend performs the actual redirect by submitting a form to eSewa.
- Frontend success and failure pages should call `GET /api/payments/esewa/success` or `GET /api/payments/esewa/failure` with the browser callback query so the backend can decode and verify it immediately.
- Redirect success is never trusted by itself.
- Backend verification decides whether the payment becomes `paid`, `failed`, `cancelled`, or `pending_review`.

Full payment details: `docs/multi_vendor_payment_guide.md`

## Reporting Payload Notes

- Admin dashboard returns marketplace metrics, recent orders, and top vendors.
- Admin reports return filtered summary metrics, top products, and top vendors.
- Vendor dashboard returns vendor metrics, best-selling products, and recent vendor orders.
- Vendor sales reports are always scoped to the authenticated vendor.

## Queue and Scheduler Commands

- Queue worker:
  - `docker compose exec app php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3`
- Scheduler tick:
  - `docker compose exec app php artisan schedule:run`
- Recommendation refresh:
  - `docker compose exec app php artisan recommendations:generate --limit=8`
- Stale payment cleanup:
  - `docker compose exec app php artisan payments:cleanup-stale-pending --hours=24`
- Pending payment reconciliation:
  - `docker compose exec app php artisan payments:reconcile-pending --hours=1 --limit=25`
- Failed job retry:
  - `docker compose exec app php artisan queue:retry all`
- Failed job prune:
  - `docker compose exec app php artisan queue:prune-failed --hours=48`

## Test and Seed Workflow

- Rebuild schema and seed:
  - `docker compose exec app php artisan migrate:fresh --seed --force`
- Run the full automated suite:
  - `docker compose exec app php artisan test --compact`
- Inspect API routes:
  - `docker compose exec app php artisan route:list --path=api`

## Known MVP Decisions

- Search remains SQL-based for the MVP.
- Recommendations are generated from paid-order co-purchase data.
- Apriori-specific external processing is deferred, but the recommendation persistence contract is already in place.
- Invoices are HTML files, not PDFs, in the current implementation.
