# Multi-Vendor Frontend Implementation Guide

This document is the frontend execution guide for building the full Nuxt application against the completed backend.

Primary frontend references:

- [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
- [docs/multi_vendor_frontend_pages.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_pages.md)
- [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
- [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)
- [docs/multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_ecommerce_implementation_plan.md)

Backend integration assumptions:

- backend is complete and should be treated as the source of truth
- frontend should not invent missing business logic
- frontend should consume backend statuses, totals, permissions, and payment truth exactly as returned

---

## 1. Frontend Objective

Build a Nuxt 4 frontend that:

- consumes the Laravel API cleanly
- stays strongly typed with TypeScript
- is component-focused
- keeps business and API logic inside composables
- keeps Vue page files mostly presentational
- supports public browsing, customer flows, vendor dashboard flows, and admin dashboard flows
- respects SSR and SEO for public catalog surfaces

---

## 2. Required Stack

- Nuxt 4
- Vue 3
- TypeScript
- Tailwind CSS
- Nuxt UI or a consistent reusable design system layer
- Pinia
- VueUse
- `$fetch` and `useFetch`
- Vitest
- Vue Test Utils
- Zod or VeeValidate for forms
- Chart.js or ECharts for dashboards

---

## 3. Recommended Frontend Structure

```text
frontend/
├── app.vue
├── assets/
├── components/
│   ├── admin/
│   ├── auth/
│   ├── cart/
│   ├── catalog/
│   ├── checkout/
│   ├── common/
│   ├── dashboard/
│   ├── invoice/
│   ├── layout/
│   ├── order/
│   ├── payment/
│   ├── product/
│   └── vendor/
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
```

Rules:

- page files should not own long API logic
- form submission logic should live in composables
- data transformation and normalization should live in composables or utilities
- components should stay reusable and prop-driven

---

## 4. Data Layer Rules

### API Client

Create one shared API client composable or plugin:

- inject base URL
- set `Accept: application/json`
- attach `Authorization: Bearer <token>` when available
- centralize error parsing
- centralize 401 and 403 handling

Recommended core pieces:

- `useApiClient`
- `useApiError`
- `useAuthToken`

Multipart upload rule:

- for Laravel file-upload update endpoints, send `POST` with `FormData`
- append `_method=PUT` inside the form data for update semantics
- do not rely on literal multipart `PUT` requests for vendor product/profile updates

### Type Safety

- use [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md) as the source of truth
- keep route-level request and response types
- do not create vague `any`-style domain models
- preserve backend literal unions for statuses

### Composable Responsibility

Each composable should:

- call the API
- validate route/query inputs if needed
- normalize backend quirks
- expose loading, error, data, and mutation methods
- keep components free from transport logic

Example:

- `useProductCatalog` owns filters, pagination, and response parsing
- `useCheckoutPreparation` owns the checkout mutation and returned eSewa initiation payload
- `useAdminReports` owns filters, metrics loading, and pagination state

---

## 5. Auth and Session Rules

Authentication model:

- backend uses Sanctum bearer tokens
- frontend stores token client-side
- frontend calls `GET /api/auth/me` during session bootstrap
- route access is role-based after session bootstrap

Required flows:

- customer registration
- vendor application
- unified login
- logout
- forgot password
- reset password
- email verification messaging

Recommended session bootstrap:

1. load token from storage
2. if token exists, call `/api/auth/me`
3. normalize user payload
4. store role and profile data
5. redirect to correct dashboard if entering protected auth pages

---

## 6. Pages and Components Rule

The page blueprint lives in [docs/multi_vendor_frontend_pages.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_pages.md).

Hard rule:

- page `.vue` files should mostly assemble sections, set page metadata, and bind composables

Move the following out of pages:

- API calls
- fetch retry rules
- mutation logic
- response normalization
- pagination state helpers
- filter-query synchronization logic
- payment form submission construction

Move the following into components:

- tables
- cards
- forms
- lists
- badges
- skeletons
- dialogs
- KPI blocks

Move the following into composables:

- CRUD logic
- fetch state
- form submission logic
- route param parsing
- optimistic updates where safe
- payment verification polling

---

## 7. Domain Implementation Guidance

### Public Catalog

Build first because it sets up the shared design system and SSR foundations.

Must support:

- product listing
- product detail
- category detail
- vendor directory
- vendor store page
- search results
- filter persistence in query params
- SEO metadata

### Customer Flows

Must support:

- registration/login
- address book
- cart
- checkout
- eSewa redirect handoff
- payment result and retry states
- order history
- order detail
- invoice download

### Vendor Flows

Must support:

- vendor application and pending state
- vendor dashboard
- vendor products CRUD
- inventory view from product data
- vendor orders
- vendor order status progression
- vendor reports
- vendor settings

### Admin Flows

Must support:

- dashboard
- vendor approval actions
- category management
- product moderation
- order oversight
- users listing
- reports
- commissions
- settings

---

## 8. Payment Integration Rules

Use [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md) as the frontend payment source of truth.

Critical rules:

- frontend never marks payment as successful by itself
- frontend always treats backend payment status as final truth
- frontend must submit the eSewa form exactly from backend-returned values
- frontend should poll or explicitly verify after return when needed

Recommended payment composables:

- `useCheckoutPreparation`
- `useEsewaRedirect`
- `usePaymentStatus`
- `useRetryPayment`

Recommended payment UI pages:

- checkout
- payment processing
- payment success
- payment failure
- customer order detail

---

## 9. SSR and SEO Rules

SSR and metadata matter most on public routes:

- `/`
- `/products`
- `/products/[slug]`
- `/categories/[slug]`
- `/vendors`
- `/vendors/[slug]`
- `/search`

Implement:

- page title and meta description per route
- canonical URLs
- Open Graph tags
- JSON-LD product schema on product detail pages
- indexable category and vendor pages

Do not spend SEO effort on dashboard routes.

---

## 10. Store Strategy

Use Pinia only where shared app state genuinely helps.

Recommended stores:

- `authStore`
- `cartStore`
- optional `uiStore`

Do not put every API response in Pinia by default.

Prefer composables for:

- paginated lists
- report filters
- detail page data
- settings forms

---

## 11. Error and Loading Strategy

Every meaningful page should handle:

- initial loading
- empty state
- validation errors
- permission errors
- retry path

Required shared UI:

- skeletons
- empty states
- inline form errors
- top-level toast system
- destructive action confirmation dialogs

Recommended global behavior:

- 401: clear token and send user to login if session is invalid
- 403: show unauthorized page or inline permission state
- 404: show not found page
- 422: map validation messages directly to form fields

---

## 12. Frontend Testing Rules

Frontend testing is mandatory.

Use:

- Vitest for unit and integration-style frontend tests
- Vue Test Utils for component and page testing

Rules:

- every meaningful feature must add or update tests in the same implementation phase
- every new composable with state or business logic should have direct Vitest coverage
- every reusable interactive component should have component tests
- every bug fix should add a regression test
- no phase should be treated as complete until its relevant Vitest tests pass

Recommended test structure:

```text
tests/
├── components/
├── composables/
├── middleware/
├── pages/
├── stores/
└── utils/
```

High-priority test areas:

- auth bootstrap and role redirects
- route middleware behavior
- public catalog filtering and query sync
- cart mutation flows
- checkout and eSewa handoff states
- payment status pages
- customer address and order flows
- vendor product and vendor order flows
- admin moderation, reporting, and settings flows

Recommended commands:

- `pnpm vitest`
- `pnpm vitest run`
- `pnpm vitest --watch`

---

## 13. Frontend Implementation Order

Recommended Codex execution order:

1. scaffold Nuxt app, Tailwind, Pinia, shared API client, shared types
2. build layouts, navigation, auth bootstrap, role middleware
3. build public catalog pages and shared catalog components
4. build auth pages
5. build cart and checkout
6. build payment result handling
7. build customer pages
8. build vendor pages
9. build admin pages
10. add SEO polish
11. add dashboard chart polish and performance improvements

---

## 14. Frontend Quality Rules for Codex

Codex should follow these rules when generating the frontend:

- prefer reusable components over page-specific duplicated UI
- prefer composables over inline page-side fetch logic
- keep pages thin and declarative
- do not hardcode backend response assumptions outside the shared type layer
- keep route guards role-aware
- keep all API integration typed
- keep all status badges and labels driven by backend enums
- keep all money values as backend strings until formatted for display
- keep payment flow backend-truth-driven
- add or update Vitest coverage for every implemented feature

---

## 15. What Codex Should Read Before Building

Before implementing the frontend, Codex should always read:

1. [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
2. [docs/multi_vendor_frontend_pages.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_pages.md)
3. [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
4. [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)
5. [docs/multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_ecommerce_implementation_plan.md)

If Codex follows those documents together, it should have everything needed to build the full frontend without guessing backend behavior.
