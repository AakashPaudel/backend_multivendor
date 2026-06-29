# FRONTEND_TODO

This file is the execution-ready master checklist for the Multi-Vendor Ecommerce frontend.

Source of truth used:

- [docs/multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_ecommerce_implementation_plan.md)
- [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
- [docs/multi_vendor_frontend_pages.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_pages.md)
- [docs/multi_vendor_frontend_implementation_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_implementation_guide.md)
- [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
- [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)

Success condition:

- if every checkbox in this file is completed, the frontend should be fully implemented, tested, documented, and ready to integrate with the completed backend

Execution rules:

- complete sections in order unless a dependency note explicitly allows parallel work
- keep pages thin and UI-focused
- place business logic and API integration in composables
- place reusable rendering into components
- keep route-level request and response contracts aligned with [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
- add or update Vitest coverage in every meaningful feature phase
- update this TODO after every completed implementation phase

---

## 1. Foundation and Setup

Objective:

- initialize the Nuxt frontend with the required baseline for a typed, component-focused ecommerce app

- [ ] Initialize Nuxt 4 frontend repository/app
- [ ] Enable TypeScript
- [ ] Install Tailwind CSS
- [ ] Install Pinia
- [ ] Install VueUse
- [ ] Install Vitest
- [ ] Install Vue Test Utils
- [ ] Add base project structure
  - [ ] `components/`
  - [ ] `composables/`
  - [ ] `layouts/`
  - [ ] `middleware/`
  - [ ] `pages/`
  - [ ] `plugins/`
  - [ ] `stores/`
  - [ ] `types/`
  - [ ] `utils/`
  - [ ] `tests/`
- [ ] Add formatting and linting baseline
- [ ] Add frontend README or command section

Verify:

- [ ] Nuxt app boots locally
- [ ] TypeScript checks run
- [ ] Vitest runs successfully

Done when:

- [ ] the frontend codebase is initialized and stable enough for feature implementation

---

## 2. Environment, Config, and Tooling

Objective:

- configure frontend runtime, environment variables, and local developer tooling

- [ ] Create `.env.example` for frontend runtime values
- [ ] Add backend API base URL configuration
- [ ] Add app name / site URL config
- [ ] Add image/public asset config if needed
- [ ] Add auth token persistence strategy
- [ ] Add test command scripts
- [ ] Add build command scripts
- [ ] Add preview command scripts
- [ ] Add local mock/test fixture strategy notes if needed

Verify:

- [ ] frontend reads API base URL from env
- [ ] local dev and build commands work
- [ ] Vitest command is documented and runnable

Done when:

- [ ] frontend environment and tooling setup are predictable for all contributors

---

## 3. Core Architecture and Conventions

Objective:

- establish the frontend architectural rules so features stay consistent

- [ ] Define page-thinness rule
- [ ] Define composable-first business logic rule
- [ ] Define reusable component naming and placement conventions
- [ ] Define type file organization conventions
- [ ] Define API client and request helper conventions
- [ ] Define route middleware conventions
- [ ] Define loading, empty, and error state conventions
- [ ] Define form handling conventions
- [ ] Define money/date/status formatting utility conventions
- [ ] Define test organization conventions for components, composables, pages, and middleware

Verify:

- [ ] sample page follows the chosen architecture
- [ ] sample composable owns API state and mutation logic
- [ ] sample Vitest coverage exists for the chosen architecture

Done when:

- [ ] the frontend has a repeatable implementation pattern for all domains

---

## 4. Shared UI, Layouts, and Design System

Objective:

- create the reusable visual and structural primitives the rest of the app depends on

- [ ] Implement `default` layout
- [ ] Implement `auth` layout
- [ ] Implement `customer` layout
- [ ] Implement `vendor` layout
- [ ] Implement `admin` layout
- [ ] Build shared form controls
- [ ] Build shared buttons, cards, badges, and dialogs
- [ ] Build skeleton/loading components
- [ ] Build empty/error state components
- [ ] Build breadcrumb and pagination components
- [ ] Build top-level toast/notification UI
- [ ] Build header, footer, and mobile navigation
- [ ] Build role-aware user menu

Verify:

- [ ] layouts render correctly on desktop and mobile
- [ ] shared UI components are reusable and test-covered

Done when:

- [ ] the frontend has a stable design and layout system for all pages

---

## 5. API Client, Types, Auth Bootstrap, and Middleware

Objective:

- connect the frontend to the backend safely with typed API access and route guarding

- [ ] Implement shared API client plugin/composable
- [ ] Implement typed request helpers
- [ ] Create frontend type files from [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
- [ ] Normalize known backend payload quirks in composables
- [ ] Build `authStore`
- [ ] Implement token persistence
- [ ] Implement session bootstrap with `GET /api/auth/me`
- [ ] Implement `auth` middleware
- [ ] Implement `guest` middleware
- [ ] Implement role-aware route middleware
- [ ] Implement logout handling
- [ ] Add API error parser and global auth failure handling

Verify:

- [ ] authenticated pages restore session correctly
- [ ] guest-only pages redirect authenticated users correctly
- [ ] unauthorized role access is blocked in the frontend

Done when:

- [ ] frontend can safely consume backend APIs with typed auth-aware helpers

---

## 6. Public Catalog, Search, and SEO Pages

Objective:

- implement the public browsing experience and SEO-critical pages

- [ ] Build home page
- [ ] Build products listing page
- [ ] Build product detail page
- [ ] Build category detail page
- [ ] Build vendors directory page
- [ ] Build vendor store page
- [ ] Build search page
- [ ] Implement product filtering UI
- [ ] Implement sorting UI
- [ ] Persist filters in URL query params
- [ ] Implement public pagination controls
- [ ] Add product recommendations UI
- [ ] Add SEO metadata for product/category/vendor pages
- [ ] Add product/category/vendor breadcrumbs
- [ ] Add structured product metadata where appropriate

Verify:

- [ ] product, category, vendor, and search pages render correct backend data
- [ ] filtering and sorting work against the URL and API
- [ ] SSR/SEO metadata is present on public catalog routes

Done when:

- [ ] public catalog browsing is complete, typed, and SEO-ready

---

## 7. Auth and Access Entry Flows

Objective:

- implement all frontend auth entry points and access-state screens

- [ ] Build unified login page
- [ ] Build customer registration page
- [ ] Build vendor application page
- [ ] Build forgot-password page
- [ ] Build reset-password page
- [ ] Build email verification messaging page
- [ ] Build pending vendor approval page
- [ ] Build unauthorized page
- [ ] Build not-found page
- [ ] Add auth-related redirects by role after login
- [ ] Add resend verification UX

Verify:

- [ ] customer, vendor, and admin login redirect correctly
- [ ] password reset pages handle token/email query values correctly
- [ ] vendor pending state is represented clearly in the UI

Done when:

- [ ] the frontend supports all auth-entry and access-state flows required by the backend

---

## 8. Customer Account, Addresses, Orders, and Invoice Actions

Objective:

- implement the customer account area outside cart/checkout

- [ ] Build customer dashboard page
- [ ] Build customer profile page
- [ ] Build customer addresses page
- [ ] Build customer orders list page
- [ ] Build customer order detail page
- [ ] Build invoice download/view action
- [ ] Build reusable address form
- [ ] Build order timeline and status components
- [ ] Build payment status panel for order detail page

Verify:

- [ ] customer profile and address mutations work
- [ ] customer orders render only the authenticated user’s order data
- [ ] invoice action works against the HTML invoice endpoint

Done when:

- [ ] customer account pages are fully usable and test-covered

---

## 9. Cart, Checkout, Payment Handoff, and Payment Status

Objective:

- implement cart, checkout, and eSewa handoff/status flows

- [ ] Build cart page
- [ ] Build cart store or shared cart state layer
- [ ] Implement add-to-cart flow from product pages
- [ ] Implement cart quantity update flow
- [ ] Implement cart removal flow
- [ ] Render backend-authoritative cart warnings and totals
- [ ] Build checkout page
- [ ] Implement address selection within checkout
- [ ] Implement checkout summary and vendor-order breakdown
- [ ] Implement eSewa redirect form submission from backend payload
- [ ] Build payment processing page
- [ ] Build payment success page
- [ ] Build payment failure page
- [ ] Implement payment status polling and explicit verify fallback
- [ ] Implement retry payment flow through initiate endpoint

Verify:

- [ ] cart state reflects backend totals and availability
- [ ] checkout handles multi-vendor order previews correctly
- [ ] payment result pages use backend truth rather than query params alone

Done when:

- [ ] cart, checkout, and payment flows are complete and safe for users

---

## 10. Vendor Dashboard and Operations

Objective:

- implement the vendor workspace against the completed vendor APIs

- [ ] Build vendor dashboard page
- [ ] Build vendor products list page
- [ ] Build vendor product create page
- [ ] Build vendor product edit page
- [ ] Build vendor inventory page
- [ ] Build vendor orders list page
- [ ] Build vendor order detail page
- [ ] Build vendor reports page
- [ ] Build vendor settings/profile page
- [ ] Build reusable vendor product form
- [ ] Build product image uploader
- [ ] Build vendor order status update UI
- [ ] Build best-selling products and recent-order widgets

Verify:

- [ ] vendor can manage only own products and orders in the UI
- [ ] vendor status changes and product form states align with backend rules
- [ ] vendor reports render backend metrics correctly

Done when:

- [ ] vendor-facing flows are complete and test-covered

---

## 11. Admin Dashboard and Marketplace Operations

Objective:

- implement the admin workspace against the completed admin APIs

- [ ] Build admin dashboard page
- [ ] Build vendor approvals page
- [ ] Build products moderation page
- [ ] Build categories management page
- [ ] Build orders management page
- [ ] Build users list page
- [ ] Build commissions page
- [ ] Build reports page
- [ ] Build platform settings page
- [ ] Build reusable admin tables/forms/dialogs
- [ ] Implement vendor decision dialogs
- [ ] Implement category create/update/delete flows
- [ ] Implement product moderation action UI
- [ ] Implement order override status UI
- [ ] Implement commission override editing UI

Verify:

- [ ] admin actions map to backend mutations correctly
- [ ] admin metrics and reports render correctly
- [ ] destructive/admin-only actions have confirmations and clear feedback

Done when:

- [ ] admin-facing flows are complete and test-covered

---

## 12. State, Composables, and Utilities

Objective:

- complete the reusable data/state layer that keeps pages thin

- [ ] Build domain composables for catalog
- [ ] Build domain composables for auth/session
- [ ] Build domain composables for customer area
- [ ] Build domain composables for cart/checkout/payment
- [ ] Build domain composables for vendor area
- [ ] Build domain composables for admin area
- [ ] Build pagination/query-sync utilities
- [ ] Build money/date/status formatting utilities
- [ ] Build typed fixture builders for Vitest
- [ ] Normalize backend response quirks in one place

Verify:

- [ ] page files stay UI-focused
- [ ] route-level data fetching is reusable across pages
- [ ] shared utilities are reused instead of duplicated

Done when:

- [ ] composables and utilities carry the frontend’s non-UI complexity

---

## 13. UX, Accessibility, Error Handling, and Performance

Objective:

- harden the frontend user experience across all roles and devices

- [ ] Add loading skeletons for public pages
- [ ] Add loading skeletons for dashboard pages
- [ ] Add empty states across major route groups
- [ ] Add inline and toast-based validation feedback
- [ ] Add confirmation dialogs for destructive actions
- [ ] Add mobile-responsive treatment for all route groups
- [ ] Add accessible labels, focus management, and button states
- [ ] Add graceful 401/403/404 handling
- [ ] Add retry states for failed loads
- [ ] Add payment-pending and payment-review states
- [ ] Improve SSR/public-page performance where needed
- [ ] Avoid unnecessary duplicated requests across page transitions

Verify:

- [ ] major pages work on mobile and desktop
- [ ] error and empty states exist for each major route group
- [ ] core flows remain usable under slow or failed API conditions

Done when:

- [ ] frontend UX is resilient, accessible, and polished for the implemented scope

---

## 14. Automated Testing

Objective:

- achieve reliable frontend coverage with Vitest for every meaningful feature

- [ ] Configure Vitest for the project
- [ ] Configure Vue Test Utils
- [ ] Create test setup utilities
- [ ] Add typed fixture factories from frontend API types
- [ ] Add composable tests for auth/session
- [ ] Add middleware tests for guest/auth/role handling
- [ ] Add component tests for shared form controls and dialogs
- [ ] Add component tests for catalog components
- [ ] Add component/composable tests for cart and checkout
- [ ] Add payment flow tests for redirect form generation and status handling
- [ ] Add customer page/composable tests
- [ ] Add vendor page/composable tests
- [ ] Add admin page/composable tests
- [ ] Add regression tests for bugs found during implementation
- [ ] Ensure tests are run after each feature phase
- [ ] Document frontend test commands

Verify:

- [ ] Vitest suite passes
- [ ] every major frontend domain has coverage
- [ ] new features are not merged without matching test updates

Done when:

- [ ] frontend test coverage is reliable enough to support rapid iteration

---

## 15. Documentation and Deployment Readiness

Objective:

- ensure the frontend is documented and ready for integration and delivery

- [ ] Keep API-type docs aligned with implementation
- [ ] Keep frontend pages doc aligned with implementation
- [ ] Keep frontend implementation guide aligned with implementation
- [ ] Add frontend local setup commands to README
- [ ] Add frontend env variable documentation
- [ ] Add frontend build and preview guidance
- [ ] Add deployment notes for frontend hosting
- [ ] Add backend integration notes for API base URL, auth, and payment return URLs
- [ ] Add final QA/manual test checklist

Verify:

- [ ] a contributor can set up, run, test, and build the frontend from docs alone
- [ ] deployment/env notes are actionable

Done when:

- [ ] frontend docs are complete for implementation, QA, and delivery

---

## 16. Final Acceptance Matrix

Objective:

- prove the frontend is complete against the implementation plan and backend contract

- [ ] Re-read [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md) and confirm implementation matches typed contracts
- [ ] Re-read [docs/multi_vendor_frontend_pages.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_pages.md) and confirm all required pages are implemented
- [ ] Re-read [docs/multi_vendor_frontend_implementation_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_implementation_guide.md) and confirm architecture rules were followed
- [ ] Confirm public catalog routes are implemented
- [ ] Confirm auth entry routes are implemented
- [ ] Confirm customer routes are implemented
- [ ] Confirm vendor routes are implemented
- [ ] Confirm admin routes are implemented
- [ ] Confirm all critical composables exist
- [ ] Confirm all critical reusable components exist
- [ ] Confirm Vitest coverage exists for all major domains
- [ ] Confirm docs are current
- [ ] Confirm no placeholder critical page or missing frontend flow remains

Verify:

- [ ] a fresh reviewer can trace every frontend requirement to pages, components, composables, tests, and docs
- [ ] there are no critical frontend gaps remaining for public browsing, auth, checkout, payment, customer, vendor, admin, testing, or deployment

Done when:

- [ ] every checkbox in this file is complete
- [ ] the frontend is fully implemented, tested, documented, and ready for release against the current backend
