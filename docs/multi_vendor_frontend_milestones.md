# FRONTEND_MILESTONES

This file converts [docs/multi_vendor_frontend_todo.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_todo.md) into phased frontend implementation milestones and ready-to-use Codex prompts.

Use this together with:

- [docs/multi_vendor_frontend_implementation_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_implementation_guide.md)
- [docs/multi_vendor_frontend_pages.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_pages.md)
- [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
- [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
- [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)
- [docs/multi_vendor_frontend_todo.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_todo.md)

Execution rules:

- do milestones in order unless a dependency note explicitly says parallel work is safe
- update [docs/multi_vendor_frontend_todo.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_todo.md) after each completed phase
- add or update Vitest coverage in every phase that introduces meaningful UI, state, or route behavior

---

## Phase 1. Nuxt Foundation, Tooling, and Testing Baseline

Goal:

- create the frontend application baseline with Nuxt, TypeScript, Tailwind, and Vitest

Includes:

- Nuxt app initialization
- TypeScript
- Tailwind
- Pinia
- VueUse
- Vitest and Vue Test Utils
- folder structure
- lint/format baseline
- README/local commands

Deliverables:

- Nuxt app boots
- test command exists
- shared frontend folder structure exists

Depends on:

- project plan and backend docs only

Exit criteria:

- dev server starts
- Vitest runs
- baseline project structure is ready for feature work

Codex prompt:

```text
You are implementing Phase 1 of the frontend for this project.

Read first:
- docs/multi_vendor_ecommerce_implementation_plan.md
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_frontend_todo.md

Implement only Phase 1: Nuxt Foundation, Tooling, and Testing Baseline.

Scope:
- initialize the Nuxt frontend if needed
- enable TypeScript
- install and configure Tailwind CSS
- install and configure Pinia
- install and configure VueUse
- install and configure Vitest and Vue Test Utils
- create the recommended frontend folder structure
- add baseline README/local commands if missing
- add lint/format/test scripts if missing

Constraints:
- frontend only
- do not implement business pages yet
- keep future page files thin and composable-driven

Verification:
- app boots
- typecheck works
- vitest works

Also add or update tests only where needed for the baseline and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 2. Shared Types, API Client, Auth Bootstrap, Middleware, and Layouts

Goal:

- establish the frontend data layer, session handling, and structural shell

Includes:

- shared API client
- typed request/response layer
- auth store
- session bootstrap
- guest/auth/role middleware
- default/auth/customer/vendor/admin layouts
- shared app shell pieces

Deliverables:

- typed API access works
- layouts exist
- route protection baseline exists

Depends on:

- Phase 1

Exit criteria:

- frontend can restore the current session from the backend
- layouts and middleware are reusable for later pages

Codex prompt:

```text
You are implementing Phase 2 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 2: Shared Types, API Client, Auth Bootstrap, Middleware, and Layouts.

Scope:
- create the shared API client/plugin
- create domain type files from docs/multi_vendor_frontend_api_types.md
- create auth/session bootstrap logic using /api/auth/me
- create authStore
- implement auth, guest, and role middleware
- build default, auth, customer, vendor, and admin layouts
- build shared header/footer/mobile nav/user menu shell
- normalize known backend payload quirks in one place

Constraints:
- frontend only
- keep pages thin
- composables and stores should own non-UI logic

Verification:
- session bootstrap works
- protected routes are guarded
- shared layouts render

Add Vitest coverage for middleware/session helpers and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 3. Public Catalog, Search, Vendor Directory, and SEO Surfaces

Goal:

- build the public marketplace browsing experience

Includes:

- home page
- products listing
- product detail
- category detail
- vendors directory
- vendor store page
- search page
- filters, sorts, pagination
- public SEO metadata

Deliverables:

- public catalog routes implemented
- filter/query handling implemented
- reusable catalog components implemented

Depends on:

- Phase 2

Exit criteria:

- public pages load backend data correctly
- filter and pagination state sync with the URL
- SEO baseline exists on public routes

Codex prompt:

```text
You are implementing Phase 3 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 3: Public Catalog, Search, Vendor Directory, and SEO Surfaces.

Scope:
- build the home page
- build /products
- build /products/[slug]
- build /categories/[slug]
- build /vendors
- build /vendors/[slug]
- build /search
- add filtering, sorting, pagination, and query-sync behavior
- add public catalog reusable components
- add SSR/SEO metadata for public pages

Constraints:
- frontend only
- use the exact backend response types
- keep pages UI-focused and move data logic to composables

Verification:
- public APIs render correctly
- filters/sorts/pagination work
- public pages have metadata hooks

Add Vitest coverage for catalog composables/components and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 4. Auth Entry Flows and Access-State Pages

Goal:

- implement login, registration, password reset, and access-state UX

Includes:

- login
- customer register
- vendor apply
- forgot password
- reset password
- email verification messaging
- pending vendor approval page
- unauthorized and not-found pages

Deliverables:

- all auth-entry pages implemented
- role-aware redirect behavior implemented
- auth forms reusable and typed

Depends on:

- Phase 2

Exit criteria:

- login/register/reset flows work against the backend
- role redirects and access-state pages behave correctly

Codex prompt:

```text
You are implementing Phase 4 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 4: Auth Entry Flows and Access-State Pages.

Scope:
- build /auth/login
- build /auth/register
- build /vendor/apply
- build /auth/forgot-password
- build /auth/reset-password
- build /auth/verify-email
- build /auth/pending-approval
- build /403
- build /404
- implement role-aware redirect behavior after login

Constraints:
- frontend only
- forms should use composables for submit logic
- do not duplicate auth logic inside page files

Verification:
- login and register flows work
- reset flow works
- access-state pages behave correctly

Add Vitest coverage for auth forms/composables/pages and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 5. Customer Account, Addresses, Orders, and Invoice Actions

Goal:

- build the customer account area outside of cart/checkout

Includes:

- customer dashboard
- customer profile
- customer addresses
- customer orders list
- customer order detail
- invoice action

Deliverables:

- customer account pages implemented
- address forms and order components reusable

Depends on:

- Phase 2
- Phase 4

Exit criteria:

- customer profile/address/order flows work against the backend
- invoice action is reachable from order detail

Codex prompt:

```text
You are implementing Phase 5 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 5: Customer Account, Addresses, Orders, and Invoice Actions.

Scope:
- build /customer/dashboard
- build /customer/profile
- build /customer/addresses
- build /customer/orders
- build /customer/orders/[orderNumber]
- add invoice download/view action
- build reusable address and order components

Constraints:
- frontend only
- keep pages thin
- use backend response types exactly

Verification:
- customer profile/address mutations work
- order list and detail pages render correctly
- invoice action works

Add Vitest coverage for customer composables/components/pages and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 6. Cart, Checkout, eSewa Handoff, and Payment Status Pages

Goal:

- implement the purchase flow from cart to payment result

Includes:

- cart page
- checkout page
- payment processing
- payment success
- payment failure
- cart state layer
- eSewa redirect form submission
- payment status polling and verify fallback

Deliverables:

- cart and checkout work against backend truth
- eSewa handoff UI is complete
- payment result pages exist

Depends on:

- Phase 2
- Phase 3
- Phase 5

Exit criteria:

- cart reflects backend totals and warnings
- checkout returns payment initiation payload and submits correctly
- payment result pages use backend status, not query assumptions

Codex prompt:

```text
You are implementing Phase 6 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_payment_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 6: Cart, Checkout, eSewa Handoff, and Payment Status Pages.

Scope:
- build /cart
- build /checkout
- build /payment/processing
- build /payment/success
- build /payment/failure
- create cart state/composables
- implement checkout preparation flow
- implement eSewa redirect form generation from backend payload
- implement payment status polling and explicit verify fallback
- add payment retry flow where appropriate

Constraints:
- frontend only
- never trust payment query params alone
- use backend payment status as final truth

Verification:
- cart totals render correctly
- checkout and payment handoff work
- payment result pages resolve from backend state

Add Vitest coverage for cart/checkout/payment composables/components/pages and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 7. Vendor Dashboard, Products, Orders, Reports, and Settings

Goal:

- implement the vendor workspace

Includes:

- vendor dashboard
- vendor product list/create/edit
- vendor inventory page
- vendor orders list/detail
- vendor reports
- vendor settings

Deliverables:

- vendor area implemented end to end
- reusable product and order components created

Depends on:

- Phase 2
- Phase 4

Exit criteria:

- vendor can manage products and own orders
- vendor reports and dashboard render correct backend metrics

Codex prompt:

```text
You are implementing Phase 7 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 7: Vendor Dashboard, Products, Orders, Reports, and Settings.

Scope:
- build /vendor/dashboard
- build /vendor/products
- build /vendor/products/new
- build /vendor/products/[id]/edit
- build /vendor/inventory
- build /vendor/orders
- build /vendor/orders/[id]
- build /vendor/reports
- build /vendor/settings
- implement reusable vendor product form and order status UI

Constraints:
- frontend only
- keep vendor page logic in composables
- use exact backend types and statuses

Verification:
- vendor product CRUD UI works
- vendor order status updates work
- vendor dashboard/reports render correctly

Add Vitest coverage for vendor composables/components/pages and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 8. Admin Dashboard, Marketplace Controls, Reports, and Settings

Goal:

- implement the admin workspace

Includes:

- admin dashboard
- vendor approvals
- product moderation
- categories management
- orders management
- users listing
- commissions
- reports
- platform settings

Deliverables:

- admin area implemented end to end
- reusable admin data tables/forms/dialogs created

Depends on:

- Phase 2
- Phase 4

Exit criteria:

- admin can manage marketplace operations from the frontend
- admin reports/settings/commission flows work correctly

Codex prompt:

```text
You are implementing Phase 8 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_backend_api_guide.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 8: Admin Dashboard, Marketplace Controls, Reports, and Settings.

Scope:
- build /admin/dashboard
- build /admin/vendors
- build /admin/products
- build /admin/categories
- build /admin/orders
- build /admin/users
- build /admin/commissions
- build /admin/reports
- build /admin/settings
- implement reusable admin dialogs/forms/tables

Constraints:
- frontend only
- destructive actions must use confirmation dialogs
- keep admin data loading and mutation logic in composables

Verification:
- vendor approval actions work
- category/product/order/settings/commission flows work
- admin metrics and reports render correctly

Add Vitest coverage for admin composables/components/pages and update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 9. UX Hardening, Accessibility, Testing Expansion, and SEO Completion

Goal:

- harden the frontend UX and expand test coverage across all implemented domains

Includes:

- loading states
- empty states
- error states
- accessibility improvements
- mobile polish
- SEO completion
- regression tests

Deliverables:

- resilient UX across route groups
- broad Vitest coverage
- public pages fully SEO-polished

Depends on:

- Phases 3 through 8

Exit criteria:

- major flows remain usable under loading/error conditions
- core route groups have meaningful Vitest coverage

Codex prompt:

```text
You are implementing Phase 9 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 9: UX Hardening, Accessibility, Testing Expansion, and SEO Completion.

Scope:
- add loading, empty, and error states across all major route groups
- improve responsive/mobile behavior
- improve accessibility labels, focus states, and button states
- complete SEO metadata behavior on public pages
- expand Vitest coverage across public, customer, vendor, and admin flows
- add regression tests for implementation bugs found so far

Constraints:
- frontend only
- prefer concrete fixes over documenting gaps
- do not add placeholder flows for critical UX states

Verification:
- major pages handle loading and failure cleanly
- mobile behavior is acceptable
- Vitest coverage expands for all implemented domains

Update docs/multi_vendor_frontend_todo.md when complete.
```

---

## Phase 10. Documentation, Final Test Sweep, and Deployment Readiness

Goal:

- finish the frontend with docs alignment, full test sweep, and delivery readiness

Includes:

- final docs review
- final Vitest sweep
- README/env/build docs
- deployment readiness notes
- final acceptance review against the frontend TODO

Deliverables:

- docs reflect implemented frontend
- final tests pass
- deployment/setup notes are actionable

Depends on:

- Phases 1 through 9

Exit criteria:

- frontend docs are current
- Vitest passes
- final acceptance checklist can be completed with no critical gaps

Codex prompt:

```text
You are implementing Phase 10 of the frontend for this project.

Read first:
- docs/multi_vendor_frontend_implementation_guide.md
- docs/multi_vendor_frontend_pages.md
- docs/multi_vendor_frontend_api_types.md
- docs/multi_vendor_frontend_todo.md
- docs/multi_vendor_frontend_milestones.md

Implement only Phase 10: Documentation, Final Test Sweep, and Deployment Readiness.

Scope:
- review frontend docs for accuracy against the implementation
- finalize Vitest coverage and clean up flaky or redundant patterns
- finalize local setup/build/test documentation
- add frontend deployment readiness notes
- review the frontend against docs/multi_vendor_frontend_todo.md and close remaining critical gaps

Constraints:
- frontend only
- do not leave placeholder TODOs in critical paths
- prefer concrete fixes over documenting missing work

Verification:
- vitest passes
- docs reflect the actual frontend
- deployment notes are actionable
- final acceptance matrix can be completed with no critical frontend gaps

Update docs/multi_vendor_frontend_todo.md when complete.
```
