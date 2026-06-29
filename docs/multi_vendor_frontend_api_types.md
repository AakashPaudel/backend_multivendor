# Multi-Vendor Frontend API Types

This document is the frontend source of truth for request and response typing against the current backend implementation.

Primary references:

- [docs/multi_vendor_backend_api_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_backend_api_guide.md)
- [docs/multi_vendor_payment_guide.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_payment_guide.md)
- [docs/multi_vendor_ecommerce_implementation_plan.md](/home/anish/docker-personal-projects/multi-vendor-backend/docs/multi_vendor_ecommerce_implementation_plan.md)

Base URL:

- `http://multi-vendor.localhost/api`

Important notes:

- These types match the current backend response shapes, including a few quirks that should be normalized in the frontend data layer instead of guessed away.
- Laravel paginated resource collections return `data`, `links`, and `meta`.
- Nested paginated payloads that are returned inside a larger object, such as `GET /api/vendor/reports/sales`, also use the same `data` / `links` / `meta` structure.
- Non-paginated resource collections return `data` only.
- Some auth-related user payloads can include `vendor_profile: []` for non-vendor users because of the current resource implementation. Keep that exact if you want backend-accurate typing, or normalize it in a composable before it reaches components.
- `GET /api/orders/{orderNumber}/invoice` returns HTML, not JSON.
- In `OrderDetailResponse.address`, the backend currently returns `label`, `recipient_name`, and `recipient_phone`, but the actual stored address model uses `full_name` and `phone`. Expect those three order-detail fields to be nullable.

---

## 1. Shared Types

```ts
export type UserRole = 'admin' | 'vendor' | 'customer'

export type UserStatus = 'active' | 'inactive' | 'suspended'

export type VendorApprovalStatus = 'pending' | 'approved' | 'rejected' | 'suspended'

export type ProductStatus = 'draft' | 'active' | 'inactive' | 'out_of_stock'

export type OrderStatus =
  | 'pending_payment'
  | 'payment_initiated'
  | 'paid'
  | 'processing'
  | 'partially_shipped'
  | 'completed'
  | 'cancelled'
  | 'refunded'
  | 'failed'

export type VendorOrderStatus =
  | 'new'
  | 'accepted'
  | 'packed'
  | 'shipped'
  | 'out_for_delivery'
  | 'delivered'
  | 'cancelled'
  | 'returned'

export type PaymentStatus =
  | 'pending'
  | 'initiated'
  | 'paid'
  | 'failed'
  | 'cancelled'
  | 'pending_review'
  | 'finalized'

export type PaymentVerificationStatus = 'pending' | 'verified' | 'failed' | 'mismatch'

export type ProductSort = 'newest' | 'price_asc' | 'price_desc' | 'popularity'

export interface ApiMessageResponse {
  message: string
}

export interface ApiValidationErrorResponse {
  message: string
  errors: Record<string, string[]>
}

export interface ApiErrorResponse {
  message: string
}

export interface PaginationLinkObject {
  url: string | null
  label: string
  active: boolean
}

export interface PaginatedLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginatedMeta {
  current_page: number
  from: number | null
  last_page: number
  links: PaginationLinkObject[]
  path: string
  per_page: number
  to: number | null
  total: number
}

export interface PaginatedApiResponse<T> {
  data: T[]
  links: PaginatedLinks
  meta: PaginatedMeta
}

export interface CollectionApiResponse<T> {
  data: T[]
}
```

---

## 2. Core Resource Shapes

```ts
export interface AddressResource {
  id: number
  full_name: string
  phone: string
  address_line_1: string
  address_line_2: string | null
  city: string
  district: string
  province: string | null
  postal_code: string | null
  country: string
  is_default: boolean
  created_at: string | null
  updated_at: string | null
}

export interface CustomerProfileEmbeddedUser {
  id: number
  name: string
  email: string
  phone: string
  role: UserRole
  status: UserStatus
  email_verified_at: string | null
}

export interface CustomerProfileResource {
  id: number
  default_address_id: number | null
  user?: CustomerProfileEmbeddedUser
  default_address?: AddressResource | null
}

export interface VendorProfileSummary {
  id: number
  store_name: string
  slug: string
  approval_status: VendorApprovalStatus
}

export type UserVendorProfileField = VendorProfileSummary | []

export interface UserResource {
  id: number
  name: string
  email: string
  phone: string
  role: UserRole
  status: UserStatus
  email_verified_at: string | null
  customer_profile?: CustomerProfileResource | null
  vendor_profile?: UserVendorProfileField
  default_address?: AddressResource | null
}

export interface VendorProfileResource {
  id: number
  user_id: number
  store_name: string
  slug: string
  description: string | null
  logo_path: string | null
  logo_url: string | null
  banner_path: string | null
  banner_url: string | null
  business_email: string | null
  business_phone: string | null
  address_line: string | null
  city: string | null
  district: string | null
  country: string | null
  approval_status: VendorApprovalStatus
  approved_by: number | null
  approved_at: string | null
  rejection_reason: string | null
  commission_rate_override: string | null
  created_at: string | null
  updated_at: string | null
}

export interface PublicVendorResource {
  id: number
  store_name: string
  slug: string
  description: string | null
  logo_path: string | null
  logo_url: string | null
  banner_path: string | null
  banner_url: string | null
  city: string | null
  district: string | null
  country: string | null
  product_count?: number
}

export interface CategoryResource {
  id: number
  parent_id: number | null
  name: string
  slug: string
  description: string | null
  image_path: string | null
  image_url: string | null
  is_active: boolean
  sort_order: number
  children?: CategoryResource[]
  created_at: string | null
  updated_at: string | null
}

export interface ProductImageResource {
  id: number
  image_path: string
  image_url: string | null
  sort_order: number
}

export interface ProductVendorEmbedded {
  id: number
  name: string
}

export interface ProductResource {
  id: number
  vendor_id: number
  category_id: number
  name: string
  slug: string
  sku: string
  short_description: string | null
  description: string | null
  price: string
  discount_price: string | null
  stock_quantity: number
  status: ProductStatus
  thumbnail_path: string | null
  thumbnail_url: string | null
  weight: string | null
  meta_title: string | null
  meta_description: string | null
  is_sellable: boolean
  category?: CategoryResource
  images?: ProductImageResource[]
  vendor?: ProductVendorEmbedded
  created_at: string | null
  updated_at: string | null
}

export interface CartItemProductSummary {
  id: number | null
  name: string | null
  slug: string | null
  sku: string | null
  status: ProductStatus | null
  stock_quantity: number | null
  thumbnail_path: string | null
  thumbnail_url: string | null
}

export interface CartItemVendorSummary {
  id: number | null
  store_name: string | null
  slug: string | null
}

export interface CartItemResource {
  id: number
  quantity: number
  stored_unit_price: string
  current_unit_price: string
  base_unit_price: string
  subtotal: string
  discount_total: string
  line_total: string
  is_available: boolean
  messages: string[]
  product: CartItemProductSummary
  vendor: CartItemVendorSummary
}

export interface CartTotals {
  item_count: number
  distinct_items: number
  vendor_count: number
  subtotal: string
  discount_total: string
  shipping_total: string
  tax_total: string
  grand_total: string
  is_checkout_ready: boolean
}

export interface CartResource {
  id: number
  items: CartItemResource[]
  totals: CartTotals
}

export interface CheckoutVendorOrderPreview {
  id: number
  vendor_id: number
  store_name: string | null
  subtotal: string
  commission_rate: string
  commission_amount: string
  net_amount: string
  items_count: number
}

export interface CheckoutOrderPreview {
  id: number
  order_number: string
  subtotal: string
  discount_total: string
  shipping_total: string
  tax_total: string
  grand_total: string
  payment_status: PaymentStatus
  order_status: OrderStatus
}

export interface CheckoutPaymentPreview {
  id: number
  gateway: string
  payment_method: string
  amount: string
  status: PaymentStatus
  verification_status: PaymentVerificationStatus
  transaction_uuid: string
}

export interface EsewaFields {
  amount: string
  tax_amount: string
  total_amount: string
  transaction_uuid: string
  product_code: string
  product_service_charge: string
  product_delivery_charge: string
  success_url: string
  failure_url: string
  signed_field_names: string
  signature: string
}

export interface EsewaPayload {
  gateway: 'esewa'
  form_url: string
  method: 'POST'
  fields: EsewaFields
}

export interface CheckoutPaymentInitiation extends EsewaPayload {
  amount: string
  tax_amount: string
  total_amount: string
  order_number: string
}

export interface CheckoutPreparationResponse {
  order: CheckoutOrderPreview
  totals: {
    subtotal: string
    discount_total: string
    shipping_total: string
    tax_total: string
    grand_total: string
  }
  vendor_orders: CheckoutVendorOrderPreview[]
  payment: CheckoutPaymentPreview
  payment_initiation: CheckoutPaymentInitiation
}

export interface PaymentInitiationResponse {
  order_number: string
  payment: {
    id: number
    transaction_uuid: string
    status: PaymentStatus
    verification_status: PaymentVerificationStatus
    amount: string
  }
  esewa: EsewaPayload
}

export interface PaymentStatusResponse {
  message: string
  idempotent: boolean
  verification: {
    status: string
    verified: boolean
    reason: string
  }
  order: {
    id: number
    order_number: string
    payment_status: PaymentStatus
    order_status: OrderStatus
    grand_total: string
  }
  payment: {
    id: number
    gateway: string
    payment_method: string
    transaction_uuid: string
    gateway_reference: string | null
    amount: string
    status: PaymentStatus
    verification_status: PaymentVerificationStatus
    paid_at: string | null
  }
}

export interface OrderItemVendorEmbedded {
  id: number
  name: string
  store_name: string | null
}

export interface OrderItemResource {
  id: number
  product_id: number | null
  vendor_id: number
  product_name: string
  sku: string
  unit_price: string
  quantity: number
  line_total: string
  commission_amount: string
  net_amount: string
  status: string
  vendor?: OrderItemVendorEmbedded
}

export interface OrderStatusHistoryActor {
  id: number
  name: string
  role: UserRole
}

export interface OrderStatusHistoryResource {
  id: number
  order_id: number
  vendor_order_id: number | null
  status: string
  message: string | null
  changed_by: number | null
  actor?: OrderStatusHistoryActor | null
  created_at: string | null
}

export interface OrderLatestPaymentSummary {
  id: number
  status: PaymentStatus
  verification_status: PaymentVerificationStatus
  gateway_reference: string | null
  transaction_uuid: string
  paid_at: string | null
}

export interface VendorOrderListOrderSummary {
  id: number
  order_number: string
  order_status: OrderStatus
  payment_status: PaymentStatus
  grand_total: string
  placed_at: string | null
  customer: {
    id: number
    name: string
    email: string
  } | null
  payment: {
    status: PaymentStatus
    verification_status: PaymentVerificationStatus
    gateway_reference: string | null
    paid_at: string | null
  } | null
}

export interface VendorOrderResource {
  id: number
  order_id: number
  vendor_id: number
  subtotal: string
  commission_amount: string
  net_amount: string
  status: VendorOrderStatus
  vendor?: {
    id: number
    name: string
    store_name: string | null
  }
  order?: VendorOrderListOrderSummary
  items?: OrderItemResource[]
  status_histories?: OrderStatusHistoryResource[]
  created_at: string | null
  updated_at: string | null
}

export interface OrderResource {
  id: number
  order_number: string
  user_id: number
  address_id: number
  subtotal: string
  discount_total: string
  shipping_total: string
  tax_total: string
  grand_total: string
  payment_status: PaymentStatus
  order_status: OrderStatus
  notes: string | null
  placed_at: string | null
  items_count?: number
  vendor_orders_count?: number
  latest_payment: OrderLatestPaymentSummary | null
  vendor_orders?: VendorOrderResource[]
  created_at: string | null
  updated_at: string | null
}

export interface OrderDetailAddress {
  id: number
  label: string | null
  recipient_name: string | null
  recipient_phone: string | null
  address_line_1: string
  address_line_2: string | null
  city: string
  district: string
  province: string | null
  country: string
  postal_code: string | null
}

export interface OrderDetailPayment {
  id: number
  payment_method: string
  gateway: string
  amount: string
  transaction_uuid: string
  gateway_reference: string | null
  status: PaymentStatus
  verification_status: PaymentVerificationStatus
  paid_at: string | null
  created_at: string | null
}

export interface OrderDetailResponse {
  id: number
  order_number: string
  user_id: number
  address_id: number
  subtotal: string
  discount_total: string
  shipping_total: string
  tax_total: string
  grand_total: string
  payment_status: PaymentStatus
  order_status: OrderStatus
  notes: string | null
  placed_at: string | null
  customer?: {
    id: number
    name: string
    email: string
    phone: string
  }
  address?: OrderDetailAddress | null
  payments?: OrderDetailPayment[]
  vendor_orders?: VendorOrderResource[]
  items?: OrderItemResource[]
  status_histories?: OrderStatusHistoryResource[]
  created_at: string | null
  updated_at: string | null
}

export interface RecommendationResource {
  product_id: number
  recommended_product_id: number
  support_value: string
  confidence_value: string
  lift_value: string
  generated_at: string | null
  product?: ProductResource
}

export interface AdminVendorResource {
  id: number
  approval_status: VendorApprovalStatus
  approved_at: string | null
  rejection_reason: string | null
  vendor: {
    id: number
    name: string
    email: string
    phone: string
  }
  profile: VendorProfileResource
}

export interface AdminSettingResource {
  key: string
  value: Record<string, unknown> | null
  updated_at: string | null
}

export interface AdminCommissionSnapshot {
  global_rate: string
  vendor_overrides: Array<{
    vendor_id: number
    store_name: string
    vendor_name: string | null
    rate: string
  }>
}

export interface AdminDashboardResponse {
  metrics: {
    total_users: number
    total_vendors: number
    total_customers: number
    approved_vendors: number
    pending_vendors: number
    total_orders: number
    paid_orders: number
    failed_orders: number
    cancelled_orders: number
    gross_revenue: string
    commission_earned: string
    failed_jobs: number
  }
  recent_orders: Array<{
    order_number: string
    customer_name: string | null
    grand_total: string
    payment_status: PaymentStatus
    order_status: OrderStatus
    placed_at: string | null
  }>
  top_vendors: Array<{
    vendor_id: number
    vendor_name: string
    orders_count: number
    net_sales: string
  }>
}

export interface AdminReportResponse {
  filters: {
    date_from: string | null
    date_to: string | null
  }
  summary: {
    orders_count: number
    paid_orders_count: number
    gross_revenue: string
    commission_earned: string
    pending_payments: number
    payment_success_count: number
    payment_failure_count: number
  }
  top_products: Array<{
    product_name: string
    sku: string
    quantity_sold: number
    gross_sales: string
  }>
  top_vendors: Array<{
    vendor_id: number
    vendor_name: string
    orders_count: number
    net_sales: string
  }>
}

export interface VendorDashboardResponse {
  metrics: {
    products_count: number
    low_stock_products_count: number
    vendor_orders_count: number
    paid_vendor_orders_count: number
    gross_sales: string
    net_sales: string
  }
  best_selling_products: Array<{
    product_name: string
    sku: string
    quantity_sold: number
    gross_sales: string
  }>
  recent_vendor_orders: Array<{
    id: number
    order_number: string | null
    status: VendorOrderStatus
    subtotal: string
    net_amount: string
  }>
}

export interface VendorSalesReportResponse {
  filters: {
    date_from: string | null
    date_to: string | null
  }
  summary: {
    vendor_orders_count: number
    paid_vendor_orders_count: number
    gross_sales: string
    commission_total: string
    net_sales: string
  }
  orders: PaginatedApiResponse<VendorOrderResource>
}
```

---

## 3. Auth Route Types

### `POST /api/auth/register/customer`

Request:

```ts
export interface RegisterCustomerRequest {
  name: string
  email: string
  phone: string
  password: string
  password_confirmation: string
}

export interface CustomerAuthenticatedUserResponse {
  message: 'Customer registration successful.'
  token: string
  token_type: 'Bearer'
  user: UserResource & {
    role: 'customer'
    customer_profile: CustomerProfileResource | null
    default_address?: AddressResource | null
  }
}
```

### `POST /api/auth/register/vendor`

Request:

```ts
export interface RegisterVendorRequest {
  name: string
  email: string
  phone: string
  password: string
  password_confirmation: string
  store_name: string
  slug?: string | null
  description?: string | null
  business_email?: string | null
  business_phone?: string | null
  address_line?: string | null
  city?: string | null
  district?: string | null
  country?: string | null
}

export interface VendorAuthenticatedUserResponse {
  message: 'Vendor registration successful.'
  token: string
  token_type: 'Bearer'
  user: UserResource & {
    role: 'vendor'
    vendor_profile: VendorProfileSummary | []
  }
}
```

### `POST /api/auth/login`

Request:

```ts
export interface LoginRequest {
  email: string
  password: string
  device_name?: string | null
}

export type LoginResponse = {
  message: 'Login successful.'
  token: string
  token_type: 'Bearer'
  user: UserResource
}
```

### `POST /api/auth/logout`

Response:

```ts
export interface LogoutResponse {
  message: 'Logged out successfully.'
}
```

### `GET /api/auth/me`

Response:

```ts
export type AuthMeResponse = UserResource
```

### `POST /api/auth/forgot-password`

Request:

```ts
export interface ForgotPasswordRequest {
  email: string
}

export interface ForgotPasswordResponse {
  message: 'Password reset link sent successfully.'
}
```

### `POST /api/auth/reset-password`

Request:

```ts
export interface ResetPasswordRequest {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface ResetPasswordResponse {
  message: 'Password reset successfully.'
}
```

### `POST /api/auth/email/verification-notification`

Response:

```ts
export interface SendVerificationResponse {
  message: 'Verification link sent successfully.' | 'Email address already verified.'
}
```

### `GET /api/auth/verify-email/{user}/{hash}`

Response:

```ts
export interface VerifyEmailResponse {
  message: 'Email address verified successfully.' | 'Email address already verified.'
}
```

---

## 4. Customer Route Types

### `GET /api/customer/profile`

Response:

```ts
export type CustomerProfileResponse = CustomerProfileResource
```

### `PUT /api/customer/profile`

Request:

```ts
export interface UpdateCustomerProfileRequest {
  name?: string
  phone?: string
  default_address_id?: number | null
}

export type UpdateCustomerProfileResponse = CustomerProfileResource
```

### `GET /api/customer/addresses`

Response:

```ts
export type CustomerAddressListResponse = CollectionApiResponse<AddressResource>
```

### `POST /api/customer/addresses`

### `PUT /api/customer/addresses/{address}`

Request:

```ts
export interface UpsertCustomerAddressRequest {
  full_name: string
  phone: string
  address_line_1: string
  address_line_2?: string | null
  city: string
  district: string
  province?: string | null
  postal_code?: string | null
  country?: string | null
  is_default?: boolean
}

export type CustomerAddressMutationResponse = AddressResource
```

### `DELETE /api/customer/addresses/{address}`

Response:

```ts
export type CustomerAddressDeleteResponse = void
```

---

## 5. Vendor Route Types

### `GET /api/vendor/profile`

### `PUT /api/vendor/profile`

Request:

```ts
export interface UpdateVendorProfileRequest {
  store_name?: string
  slug?: string | null
  description?: string | null
  business_email?: string | null
  business_phone?: string | null
  address_line?: string | null
  city?: string | null
  district?: string | null
  country?: string | null
  logo?: File | null
  banner?: File | null
}

export type VendorProfileResponse = VendorProfileResource
```

### `GET /api/vendor/dashboard`

Response:

```ts
export type VendorDashboardApiResponse = VendorDashboardResponse
```

### `GET /api/vendor/products`

Response:

```ts
export type VendorProductListResponse = PaginatedApiResponse<ProductResource>
```

### `POST /api/vendor/products`

### `PUT /api/vendor/products/{product}`

Request:

```ts
export interface UpsertVendorProductRequest {
  category_id: number
  name: string
  slug?: string | null
  sku?: string | null
  short_description?: string | null
  description?: string | null
  price: number | string
  discount_price?: number | string | null
  stock_quantity: number
  status?: ProductStatus
  thumbnail?: File | null
  images?: File[] | null
  weight?: number | string | null
  meta_title?: string | null
  meta_description?: string | null
}

export type VendorProductMutationResponse = ProductResource
```

### `GET /api/vendor/products/{product}`

Response:

```ts
export type VendorProductDetailResponse = ProductResource
```

### `DELETE /api/vendor/products/{product}`

Response:

```ts
export type VendorProductDeleteResponse = void
```

### `GET /api/vendor/orders`

Response:

```ts
export type VendorOrderListResponse = PaginatedApiResponse<VendorOrderResource>
```

### `GET /api/vendor/orders/{vendorOrder}`

Response:

```ts
export type VendorOrderDetailResponse = VendorOrderResource
```

### `PUT /api/vendor/orders/{vendorOrder}/status`

Request:

```ts
export interface UpdateVendorOrderStatusRequest {
  status: VendorOrderStatus
  message?: string | null
}

export type UpdateVendorOrderStatusResponse = VendorOrderResource
```

### `GET /api/vendor/reports/sales`

Query params:

```ts
export interface VendorSalesReportQuery {
  date_from?: string
  date_to?: string
  per_page?: number
}

export type VendorSalesReportApiResponse = VendorSalesReportResponse
```

Response shape note:

- `orders` is a nested paginated payload with the standard Laravel resource paginator shape:
  - `orders.data`
  - `orders.links`
  - `orders.meta`
- Example frontend-safe access:
  - `response.orders.meta.current_page`
  - `response.orders.data`

---

## 6. Admin Route Types

### `GET /api/admin/dashboard`

```ts
export type AdminDashboardApiResponse = AdminDashboardResponse
```

### `GET /api/admin/reports`

### `GET /api/admin/users`

Query params:

```ts
export interface AdminReportFilterQuery {
  date_from?: string
  date_to?: string
  per_page?: number
  role?: UserRole
  status?: UserStatus
}

export type AdminReportsApiResponse = AdminReportResponse
export type AdminUsersListResponse = PaginatedApiResponse<UserResource>
```

### `GET /api/admin/vendors`

Response:

```ts
export type AdminVendorListResponse = PaginatedApiResponse<AdminVendorResource>
```

### `PUT /api/admin/vendors/{vendorProfile}/approve`

### `PUT /api/admin/vendors/{vendorProfile}/reject`

### `PUT /api/admin/vendors/{vendorProfile}/suspend`

Request:

```ts
export interface VendorDecisionRequest {
  reason?: string | null
}

export type AdminVendorDecisionResponse = AdminVendorResource
```

### `GET /api/admin/categories`

Response:

```ts
export type AdminCategoryListResponse = PaginatedApiResponse<CategoryResource>
```

### `POST /api/admin/categories`

### `PUT /api/admin/categories/{category}`

Request:

```ts
export interface UpsertCategoryRequest {
  parent_id?: number | null
  name: string
  slug?: string | null
  description?: string | null
  image?: File | null
  is_active?: boolean
  sort_order?: number
}

export type CategoryMutationResponse = CategoryResource
```

### `DELETE /api/admin/categories/{category}`

```ts
export type AdminCategoryDeleteResponse = void
```

### `GET /api/admin/products`

```ts
export type AdminProductListResponse = PaginatedApiResponse<ProductResource>
```

### `PUT /api/admin/products/{product}/status`

```ts
export interface UpdateAdminProductStatusRequest {
  status: ProductStatus
}

export type UpdateAdminProductStatusResponse = ProductResource
```

### `GET /api/admin/orders`

```ts
export type AdminOrderListResponse = PaginatedApiResponse<OrderResource>
```

### `GET /api/admin/orders/{order}`

### `PUT /api/admin/orders/{order}/status`

```ts
export interface UpdateAdminOrderStatusRequest {
  status: OrderStatus
  message?: string | null
}

export type AdminOrderDetailResponse = OrderDetailResponse
```

### `GET /api/admin/settings`

### `PUT /api/admin/settings`

```ts
export interface UpdatePlatformSettingsRequest {
  settings: Array<{
    key: string
    value?: Record<string, unknown> | null
  }>
}

export type AdminSettingsListResponse = CollectionApiResponse<AdminSettingResource>
```

### `GET /api/admin/commissions`

### `PUT /api/admin/commissions`

```ts
export interface UpdateCommissionSettingsRequest {
  global_rate: number | string
  vendor_overrides?: Array<{
    vendor_id: number
    rate?: number | string | null
  }>
}

export type AdminCommissionResponse = AdminCommissionSnapshot
```

---

## 7. Public Catalog Route Types

### `GET /api/categories`

```ts
export type PublicCategoryListResponse = PaginatedApiResponse<CategoryResource>
```

### `GET /api/categories/{slug}`

```ts
export type PublicCategoryDetailResponse = CategoryResource
```

### `GET /api/products`

Query params:

```ts
export interface ProductListQuery {
  category?: string
  vendor?: string
  min_price?: number
  max_price?: number
  sort?: ProductSort
  per_page?: number
}

export type PublicProductListResponse = PaginatedApiResponse<ProductResource>
```

### `GET /api/products/{slug}`

```ts
export type PublicProductDetailResponse = ProductResource
```

### `GET /api/vendors`

Query params:

```ts
export interface VendorListQuery {
  per_page?: number
}

export type PublicVendorListResponse = PaginatedApiResponse<PublicVendorResource>
```

### `GET /api/vendors/{slug}`

```ts
export type PublicVendorDetailResponse = PublicVendorResource
```

### `GET /api/search`

Query params:

```ts
export interface SearchQuery extends ProductListQuery {
  q?: string
}

export type SearchResponse = PaginatedApiResponse<ProductResource>
```

### `GET /api/recommendations/{product}`

```ts
export type RecommendationListResponse = CollectionApiResponse<RecommendationResource>
```

---

## 8. Cart and Checkout Route Types

### `GET /api/cart`

```ts
export type GetCartResponse = CartResource
```

### `POST /api/cart/items`

```ts
export interface AddCartItemRequest {
  product_id: number
  quantity: number
}

export type AddCartItemResponse = CartResource
```

### `PUT /api/cart/items/{cartItem}`

```ts
export interface UpdateCartItemRequest {
  quantity: number
}

export type UpdateCartItemResponse = CartResource
```

### `DELETE /api/cart/items/{cartItem}`

```ts
export type DeleteCartItemResponse = CartResource
```

### `POST /api/checkout`

```ts
export interface PrepareCheckoutRequest {
  address_id: number
  notes?: string | null
}

export type PrepareCheckoutApiResponse = CheckoutPreparationResponse
```

---

## 9. Payment Route Types

### `POST /api/payments/esewa/initiate`

```ts
export interface InitiateEsewaPaymentRequest {
  order_number: string
}

export type InitiateEsewaPaymentResponse = PaymentInitiationResponse
```

### `GET /api/payments/esewa/success`

### `GET /api/payments/esewa/failure`

These are backend callback routes. The frontend should not rely on them as normal app-navigation API calls, but if they are hit directly they return:

```ts
export type EsewaCallbackResponse = PaymentStatusResponse
```

### `POST /api/payments/esewa/verify`

```ts
export interface VerifyEsewaPaymentRequest {
  order_number: string
  transaction_uuid?: string | null
}

export type VerifyEsewaPaymentResponse = PaymentStatusResponse
```

### `GET /api/payments/{orderNumber}/status`

```ts
export type PaymentStatusApiResponse = PaymentStatusResponse
```

---

## 10. Order Route Types

### `GET /api/orders`

```ts
export type CustomerOrderListResponse = PaginatedApiResponse<OrderResource>
```

### `GET /api/orders/{orderNumber}`

```ts
export type CustomerOrderDetailResponse = OrderDetailResponse
```

### `GET /api/orders/{orderNumber}/invoice`

```ts
export type CustomerInvoiceHtmlResponse = string
```

Content type:

```ts
'text/html; charset=UTF-8'
```

---

## 11. Frontend Normalization Recommendations

These do not change the backend contract, but they will make frontend development cleaner.

```ts
export interface FrontendSessionUser extends Omit<UserResource, 'vendor_profile'> {
  vendor_profile: VendorProfileSummary | null
}
```

Recommended normalization rules:

- Convert `vendor_profile: []` to `vendor_profile: null`
- Treat missing optional nested resource keys as `null` in composables before data reaches page components
- Normalize all money strings to display helpers rather than converting to floating point in components
- Keep raw ISO date strings in stores/composables and format only in presentational components

---

## 12. Suggested Nuxt Type File Breakdown

Recommended frontend file structure:

```text
types/
├── api.ts
├── auth.ts
├── catalog.ts
├── cart.ts
├── checkout.ts
├── payment.ts
├── order.ts
├── vendor.ts
├── admin.ts
└── shared.ts
```

Recommended export strategy:

- put generic wrappers in `shared.ts`
- put request/response contracts next to their domain
- keep route-level aliases such as `GetCartResponse` and `CustomerOrderListResponse`
- keep backend-exact literal unions for statuses in one shared file

---

## 13. Testing and Fixtures Note

Use this file as the fixture contract source for Vitest.

Recommended approach:

- create typed fixture builders from these interfaces
- mock route-level responses with exact response types, not loose objects
- keep at least one fixture variant for each important status union
- update fixtures when backend response contracts change

High-value fixture groups:

- auth users by role
- paginated product lists
- cart with valid and invalid items
- checkout preparation payload
- payment status states
- order detail with status history
- vendor dashboard and reports
- admin dashboard and reports

This file should be enough to build the frontend fetch layer, composables, stores, and mutation contracts without reverse-engineering the backend.
