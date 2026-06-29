# Multi-Vendor Frontend Pages

This document is the frontend page and component blueprint for the current system.

Primary references:

- [docs/multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_ecommerce_implementation_plan.md)
- [docs/multi_vendor_frontend_api_types.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_frontend_api_types.md)
- [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
- [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)

Frontend architecture rules:

- pages should focus on layout, page composition, route params, and SEO only
- reusable business logic belongs in composables
- shared UI belongs in components
- API calls should be centralized in composables or service-like fetch helpers
- form state and mutations should be encapsulated in composables
- components should be reused aggressively across customer, vendor, and admin areas

---

## 1. Layouts

### `layouts/default.vue`

Use for public pages.

Responsibilities:

- main header
- search entry
- cart indicator
- footer
- mobile navigation
- top-level toast and modal mounts

Shared components:

- `layout/AppHeader`
- `layout/AppFooter`
- `layout/MainNav`
- `common/SearchBar`
- `common/CartButton`
- `common/UserMenu`

### `layouts/auth.vue`

Use for login, register, forgot password, reset password, vendor apply, email verification states.

Responsibilities:

- centered auth card shell
- split-screen or compact responsive auth layout
- auth messaging and redirect handling

### `layouts/customer.vue`

Use for customer account pages.

Responsibilities:

- customer sidebar
- account breadcrumbs
- account-level page title area

### `layouts/vendor.vue`

Use for vendor dashboard pages.

Responsibilities:

- vendor sidebar
- store summary header
- quick actions

### `layouts/admin.vue`

Use for admin pages.

Responsibilities:

- admin sidebar
- global filters/actions
- metrics-friendly content area

---

## 2. Public Pages

### `/`

Purpose:

- landing page and marketplace entry point

Sections:

- hero banner
- featured/newest products
- category highlights
- featured vendors
- search entry
- benefits / trust / payment reassurance

Data:

- `GET /api/products?sort=newest`
- `GET /api/categories`
- `GET /api/vendors`

Reusable components:

- `product/ProductCard`
- `product/ProductGrid`
- `catalog/CategoryCard`
- `vendor/VendorCard`
- `common/SectionHeader`

Composables:

- `useProductCatalog`
- `useCategoryCatalog`
- `useVendorCatalog`

### `/products`

Purpose:

- full product browsing page

Features:

- server-friendly filter URL query params
- sort control
- category filter
- vendor filter
- min/max price filter
- pagination
- empty state

Data:

- `GET /api/products`

Reusable components:

- `product/ProductGrid`
- `product/ProductFilters`
- `common/SortSelect`
- `common/PaginationControls`
- `common/EmptyState`

Composables:

- `useProductCatalog`
- `useRouteQueryState`

### `/products/[slug]`

Purpose:

- product detail and conversion page

Sections:

- gallery
- product header
- price block
- stock and sellability status
- vendor summary
- category breadcrumb
- add-to-cart control
- related products

Data:

- `GET /api/products/{slug}`
- `GET /api/recommendations/{productId}`

Reusable components:

- `product/ProductGallery`
- `product/ProductPrice`
- `product/AddToCartForm`
- `product/ProductMeta`
- `product/RelatedProducts`
- `vendor/VendorInlineCard`
- `common/Breadcrumbs`

Composables:

- `useProductDetail`
- `useRecommendations`
- `useCartMutations`

### `/categories/[slug]`

Purpose:

- category detail page with nested children and filtered product discovery

Sections:

- category header
- child categories
- category product grid

Data:

- `GET /api/categories/{slug}`
- `GET /api/products?category={slug}`

Reusable components:

- `catalog/CategoryHero`
- `catalog/SubcategoryList`
- `product/ProductGrid`

### `/vendors`

Purpose:

- public vendor directory

Why this page is needed:

- backend exposes `GET /api/vendors`
- it is useful for SEO and discovery even though the original plan only named vendor detail pages

Data:

- `GET /api/vendors`

Reusable components:

- `vendor/VendorCard`
- `common/PaginationControls`

### `/vendors/[slug]`

Purpose:

- vendor storefront page

Sections:

- store branding
- store details
- vendor product grid

Data:

- `GET /api/vendors/{slug}`
- `GET /api/products?vendor={slug}`

Reusable components:

- `vendor/VendorHero`
- `product/ProductGrid`

### `/search`

Purpose:

- dedicated search results page

Features:

- query from URL
- same filters as product listing
- pagination
- optional search term highlight

Data:

- `GET /api/search`

Reusable components:

- reuse the same filter and result components as `/products`

### Optional content pages

- `/about`
- `/contact`
- `/faq`

These are in the implementation plan but not backend-driven. Keep them static or CMS-lite.

---

## 3. Auth and Access Pages

### `/auth/login`

Purpose:

- unified login page for admin, vendor, and customer

Behavior:

- call `POST /api/auth/login`
- immediately call `GET /api/auth/me`
- redirect by role

Reusable components:

- `auth/LoginForm`
- `auth/AuthCard`

Composables:

- `useLogin`
- `useCurrentUser`

### `/auth/register`

Purpose:

- customer registration

Behavior:

- call `POST /api/auth/register/customer`
- show verification message state

Components:

- `auth/CustomerRegisterForm`

### `/vendor/apply`

Purpose:

- vendor registration/application page

Behavior:

- call `POST /api/auth/register/vendor`
- show pending approval state after success

Components:

- `auth/VendorApplyForm`
- `vendor/VendorApplicationSummary`

### `/auth/forgot-password`

Purpose:

- request reset link

Behavior:

- call `POST /api/auth/forgot-password`

### `/auth/reset-password`

Purpose:

- complete password reset

Behavior:

- read `token` and `email` from query
- call `POST /api/auth/reset-password`

### `/auth/verify-email`

Purpose:

- frontend landing page for verification messaging

Notes:

- backend verification route is the API route
- frontend page should explain verification status and allow resending

### `/auth/pending-approval`

Purpose:

- vendor-only informational page after application

Why this page is useful:

- backend already supports pending vendor state
- vendor users need a dedicated blocked-state page until approved

### `/403`

Purpose:

- unauthorized page

### `/404`

Purpose:

- route-not-found page

---

## 4. Customer Pages

### `/cart`

Purpose:

- cart review and cart mutation page

Features:

- list grouped by vendor
- quantity update
- remove item
- availability warnings
- authoritative totals
- login-required checkpoint for checkout

Data:

- `GET /api/cart`
- `POST /api/cart/items`
- `PUT /api/cart/items/{id}`
- `DELETE /api/cart/items/{id}`

Components:

- `cart/CartItemRow`
- `cart/CartVendorGroup`
- `cart/CartSummaryCard`
- `cart/CartWarnings`

Composables:

- `useCart`
- `useCartMutations`

### `/checkout`

Purpose:

- address selection and checkout confirmation page

Features:

- default address selection
- add/edit address inline modal or drawer
- order note
- vendor-order breakdown
- payment CTA

Data:

- `GET /api/customer/addresses`
- `POST /api/customer/addresses`
- `PUT /api/customer/addresses/{id}`
- `DELETE /api/customer/addresses/{id}`
- `POST /api/checkout`

Components:

- `checkout/AddressSelector`
- `checkout/AddressForm`
- `checkout/CheckoutSummary`
- `checkout/VendorOrderBreakdown`
- `checkout/PlaceOrderButton`

Composables:

- `useCustomerAddresses`
- `useCheckoutPreparation`
- `useEsewaRedirect`

### `/payment/processing`

Purpose:

- temporary state after returning from eSewa and before backend truth is settled

Behavior:

- call `GET /api/payments/{orderNumber}/status`
- optionally call `POST /api/payments/esewa/verify`

### `/payment/success`

### `/payment/failure`

Purpose:

- customer-facing payment result pages

Behavior:

- do not trust query params alone
- always load backend payment status

### `/customer/dashboard`

Purpose:

- quick overview for customer account

Recommended content:

- recent orders
- current profile summary
- default address summary
- quick links to cart, orders, profile

Backend note:

- there is no dedicated customer dashboard API, so compose this page from `auth/me`, `customer/profile`, and `orders`

### `/customer/orders`

Purpose:

- order history

Data:

- `GET /api/orders`

Components:

- `order/OrderList`
- `order/OrderStatusBadge`
- `common/PaginationControls`

### `/customer/orders/[orderNumber]`

Purpose:

- order detail and tracking

Data:

- `GET /api/orders/{orderNumber}`
- `GET /api/payments/{orderNumber}/status`

Sections:

- top-level order summary
- latest payment state
- vendor order breakdown
- order item list
- status history timeline
- shipping address
- invoice action

Components:

- `order/OrderSummaryCard`
- `order/VendorOrderSection`
- `order/OrderTimeline`
- `payment/PaymentStatusPanel`
- `invoice/InvoiceDownloadButton`

### `/customer/profile`

Purpose:

- account profile editing page

Data:

- `GET /api/customer/profile`
- `PUT /api/customer/profile`

### `/customer/addresses`

Purpose:

- dedicated address book page

Data:

- `GET /api/customer/addresses`
- `POST /api/customer/addresses`
- `PUT /api/customer/addresses/{id}`
- `DELETE /api/customer/addresses/{id}`

### Optional `/customer/wishlist`

Keep this out of the initial frontend unless you intentionally add wishlist support later. The current backend does not implement it.

---

## 5. Vendor Pages

### `/vendor/dashboard`

Purpose:

- vendor overview page

Data:

- `GET /api/vendor/dashboard`

Sections:

- KPI cards
- low stock summary
- best-selling products
- recent vendor orders

Components:

- `dashboard/KpiCard`
- `vendor/BestSellingProductsCard`
- `vendor/RecentVendorOrdersCard`

### `/vendor/products`

Purpose:

- vendor product management list

Features:

- product table or grid
- search/filter on client side if desired
- status badge
- quick edit
- delete confirmation

Data:

- `GET /api/vendor/products`
- `DELETE /api/vendor/products/{id}`

Components:

- `vendor/VendorProductTable`
- `product/ProductStatusBadge`
- `common/ConfirmDialog`

### `/vendor/products/new`

### `/vendor/products/[id]/edit`

Purpose:

- create and update product forms

Data:

- `POST /api/vendor/products`
- `GET /api/vendor/products/{id}`
- `PUT /api/vendor/products/{id}`
- `GET /api/categories`

Components:

- `product/ProductForm`
- `product/ProductImageUploader`
- `product/ProductSeoFields`

Composables:

- `useVendorProductForm`
- `useCategories`

### `/vendor/inventory`

Purpose:

- inventory-focused view derived from vendor products

Why this page is still useful:

- the backend does not have a separate inventory endpoint
- inventory can be composed from vendor products and low-stock indicators

Features:

- stock sorting
- low-stock highlighting
- quick stock edits through product update

### `/vendor/orders`

Purpose:

- vendor order management list

Data:

- `GET /api/vendor/orders`

### `/vendor/orders/[id]`

Purpose:

- vendor order detail and status progression page

Data:

- `GET /api/vendor/orders/{id}`
- `PUT /api/vendor/orders/{id}/status`

Components:

- `order/VendorOrderHeader`
- `order/VendorOrderItems`
- `order/VendorOrderStatusForm`
- `order/OrderTimeline`

### `/vendor/reports`

Purpose:

- vendor sales and earnings reporting page

Data:

- `GET /api/vendor/reports/sales`

Sections:

- filter bar
- summary cards
- sales table

### `/vendor/settings`

Purpose:

- vendor store profile settings page

Data:

- `GET /api/vendor/profile`
- `PUT /api/vendor/profile`

---

## 6. Admin Pages

### `/admin/dashboard`

Purpose:

- marketplace summary and operational pulse

Data:

- `GET /api/admin/dashboard`

Sections:

- user/vendor/order/payment KPIs
- recent orders
- top vendors
- failed jobs indicator

### `/admin/vendors`

Purpose:

- vendor approval and vendor state management

Data:

- `GET /api/admin/vendors`
- `PUT /api/admin/vendors/{id}/approve`
- `PUT /api/admin/vendors/{id}/reject`
- `PUT /api/admin/vendors/{id}/suspend`

Components:

- `admin/VendorApprovalTable`
- `admin/VendorDecisionDialog`
- `vendor/VendorProfilePreview`

### `/admin/products`

Purpose:

- product moderation view

Data:

- `GET /api/admin/products`
- `PUT /api/admin/products/{id}/status`

### `/admin/categories`

Purpose:

- category CRUD management

Data:

- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `PUT /api/admin/categories/{id}`
- `DELETE /api/admin/categories/{id}`

Components:

- `admin/CategoryTreeTable`
- `catalog/CategoryForm`

### `/admin/orders`

Purpose:

- order operations and support page

Data:

- `GET /api/admin/orders`
- `GET /api/admin/orders/{id}`
- `PUT /api/admin/orders/{id}/status`

### `/admin/users`

Purpose:

- user management and user inspection page

Data:

- `GET /api/admin/users`

### `/admin/commissions`

Purpose:

- commission settings page

Data:

- `GET /api/admin/commissions`
- `PUT /api/admin/commissions`

Components:

- `admin/CommissionSettingsForm`
- `admin/VendorCommissionOverrideTable`

### `/admin/reports`

Purpose:

- analytics and filtered reporting page

Data:

- `GET /api/admin/reports`

Sections:

- date range filters
- summary KPI cards
- top products
- top vendors

### `/admin/settings`

Purpose:

- platform settings page

Data:

- `GET /api/admin/settings`
- `PUT /api/admin/settings`

Components:

- `admin/PlatformSettingsForm`

---

## 7. Shared Components

These should be built before or alongside page work.

### Common

- `common/AppButton`
- `common/AppInput`
- `common/AppTextarea`
- `common/AppSelect`
- `common/AppCheckbox`
- `common/AppModal`
- `common/AppDrawer`
- `common/ConfirmDialog`
- `common/PaginationControls`
- `common/EmptyState`
- `common/ErrorState`
- `common/LoadingSkeleton`
- `common/PriceText`
- `common/DateText`
- `common/StatusBadge`
- `common/Breadcrumbs`

### Auth

- `auth/LoginForm`
- `auth/CustomerRegisterForm`
- `auth/VendorApplyForm`
- `auth/ForgotPasswordForm`
- `auth/ResetPasswordForm`
- `auth/EmailVerificationNotice`

### Product and Catalog

- `product/ProductCard`
- `product/ProductGrid`
- `product/ProductGallery`
- `product/ProductPrice`
- `product/ProductStatusBadge`
- `product/ProductFilters`
- `product/ProductForm`
- `product/ProductImageUploader`
- `product/ProductSeoFields`
- `catalog/CategoryCard`
- `catalog/CategoryHero`
- `catalog/SubcategoryList`
- `vendor/VendorCard`
- `vendor/VendorHero`
- `vendor/VendorInlineCard`

### Cart and Checkout

- `cart/CartItemRow`
- `cart/CartVendorGroup`
- `cart/CartSummaryCard`
- `cart/CartWarnings`
- `checkout/AddressSelector`
- `checkout/AddressForm`
- `checkout/CheckoutSummary`
- `checkout/VendorOrderBreakdown`
- `checkout/PaymentRedirectForm`

### Orders and Payments

- `order/OrderList`
- `order/OrderSummaryCard`
- `order/OrderTimeline`
- `order/OrderStatusBadge`
- `order/VendorOrderSection`
- `order/VendorOrderStatusForm`
- `payment/PaymentStatusPanel`
- `invoice/InvoiceDownloadButton`

### Dashboard and Admin

- `dashboard/KpiCard`
- `dashboard/MetricGrid`
- `dashboard/ChartCard`
- `admin/VendorApprovalTable`
- `admin/VendorDecisionDialog`
- `admin/CategoryTreeTable`
- `admin/CommissionSettingsForm`
- `admin/VendorCommissionOverrideTable`
- `admin/PlatformSettingsForm`
- `vendor/VendorProductTable`
- `vendor/RecentVendorOrdersCard`
- `vendor/BestSellingProductsCard`

---

## 8. Core Composables

Build these as the real frontend logic layer.

### App and Session

- `useApiClient`
- `useApiError`
- `useCurrentUser`
- `useAuth`
- `useRoleAccess`
- `useAuthRedirect`

### Catalog

- `useCategories`
- `useCategoryCatalog`
- `useProductCatalog`
- `useProductDetail`
- `useVendorCatalog`
- `useVendorStore`
- `useRecommendations`
- `useSearch`

### Cart and Checkout

- `useCart`
- `useCartMutations`
- `useCustomerAddresses`
- `useCheckoutPreparation`
- `useEsewaRedirect`
- `usePaymentStatus`

### Customer

- `useCustomerProfile`
- `useCustomerOrders`
- `useCustomerOrderDetail`
- `useInvoiceDownload`

### Vendor

- `useVendorDashboard`
- `useVendorProducts`
- `useVendorProductForm`
- `useVendorOrders`
- `useVendorOrderDetail`
- `useVendorReports`
- `useVendorProfile`

### Admin

- `useAdminDashboard`
- `useAdminReports`
- `useAdminUsers`
- `useAdminVendors`
- `useAdminCategories`
- `useAdminProducts`
- `useAdminOrders`
- `useAdminCommissions`
- `useAdminSettings`

### Utilities

- `usePagination`
- `useRouteQueryState`
- `useFilterState`
- `useToastMessages`
- `useConfirmAction`

---

## 9. Recommended Stores

Keep stores light and persistent only where global state is truly needed.

### `authStore`

Own:

- token
- current user
- auth bootstrap status

### `cartStore`

Own:

- current cart snapshot
- optimistic pending mutation flags

### Optional lightweight UI stores

- `uiStore` for layout sidebar state and global dialogs
- `settingsStore` only if frontend-wide settings are needed repeatedly

Avoid overusing stores for server state that can live in composables.

---

## 10. Middleware

### `auth`

- blocks unauthenticated access

### `guest`

- redirects authenticated users away from login/register

### `role`

- ensures route-level role access for customer, vendor, and admin route groups

### `vendor-approved`

- optional middleware for vendor pages that should only be usable after approval

Recommended behavior:

- pending/rejected/suspended vendors can still access profile/settings pages
- blocked states should redirect to `/auth/pending-approval` or an equivalent vendor-status page

---

## 11. SEO and Routing Notes

Prioritize SSR and metadata for:

- `/products/[slug]`
- `/categories/[slug]`
- `/vendors/[slug]`
- `/products`
- `/search`

Implement:

- canonical URLs
- route-driven titles/descriptions
- product/category/vendor Open Graph
- product JSON-LD
- filter persistence in query params for listing/search pages

---

## 12. Frontend Build Order

Recommended order:

1. app shell, layouts, API client, auth bootstrap
2. public catalog pages and shared catalog components
3. auth pages and middleware
4. cart and checkout
5. payment return/status pages
6. customer account pages
7. vendor dashboard pages
8. admin dashboard pages
9. SEO polish
10. loading states, empty states, and UX hardening

---

## 13. Frontend Testing Expectations

Use Vitest for frontend testing.

Rules:

- every page family should have corresponding composable and component tests
- every new feature must add or update tests in the same phase
- no feature phase is complete until its relevant Vitest tests pass

Minimum coverage expectations:

- public catalog:
  - listing filters and query sync
  - product detail rendering
  - recommendation rendering
- auth:
  - login/register form behavior
  - auth redirects
  - role middleware handling
- cart and checkout:
  - cart mutation UI
  - totals rendering from backend payloads
  - eSewa redirect form generation
- customer area:
  - address form and list states
  - order list/detail rendering
  - invoice action behavior
- vendor area:
  - product form states
  - vendor order status updates
  - vendor dashboard widgets
- admin area:
  - vendor approval actions
  - category/product management screens
  - reports/settings forms

Recommended test ownership:

- composable tests verify API/state logic
- component tests verify rendering and emitted events
- page tests verify route-level composition and middleware outcomes

If Codex follows this document together with the API types document, it should be able to build the full frontend without needing to reverse-engineer backend behavior from source files.
