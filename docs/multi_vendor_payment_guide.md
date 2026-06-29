# Multi-Vendor Payment Guide

This guide explains how payment works in this backend, how the frontend should integrate with it, and how the eSewa verification flow is kept safe and idempotent.

Primary backend payment routes:

- `POST /api/payments/esewa/initiate`
- `GET /api/payments/esewa/success`
- `GET /api/payments/esewa/failure`
- `POST /api/payments/esewa/verify`
- `GET /api/payments/{orderNumber}/status`

Official eSewa references used for this implementation:

- ePay overview and integration: `https://developer.esewa.com.np/pages/Epay`
- transaction status check is treated as the backend source of truth

## 1. Payment Flow Overview

The payment flow starts after checkout preparation.

1. Customer builds cart.
2. Customer calls `POST /api/checkout`.
3. Backend creates:
   - top-level order
   - vendor orders
   - order items
   - initial payment record in `initiated` state
4. Backend returns eSewa initiation payload.
5. Frontend submits the customer to eSewa using the returned form fields.
6. eSewa redirects the browser to backend success or failure URL.
7. Backend does not trust the redirect alone.
8. Backend performs server-side status verification against eSewa.
9. If verification succeeds, backend finalizes payment exactly once.
10. Frontend can poll `GET /api/payments/{orderNumber}/status` if needed.

## 2. Security Rules

- Never mark an order as paid from redirect data alone.
- Always verify the transaction against eSewa status check.
- Match backend payment amount with gateway verified amount.
- Match backend transaction UUID with the payment attempt being verified.
- Store raw request and response payloads for audit/debugging.
- Repeated callbacks must be idempotent.

## 3. What Checkout Returns

`POST /api/checkout` returns:

- order summary
- payment summary
- `payment_initiation`

`payment_initiation` contains the eSewa form destination and fields needed by the frontend.

Important fields:

- `form_url`
- `fields.amount`
- `fields.tax_amount`
- `fields.total_amount`
- `fields.transaction_uuid`
- `fields.product_code`
- `fields.product_service_charge`
- `fields.product_delivery_charge`
- `fields.success_url`
- `fields.failure_url`
- `fields.signed_field_names`
- `fields.signature`

## 4. Frontend Integration Steps

Recommended frontend flow:

1. Call `POST /api/checkout`.
2. Save the returned `order.order_number`.
3. Read `payment_initiation`.
4. Create an HTML form or redirect flow that posts all returned `fields` to `form_url`.
5. Send the browser to eSewa.
6. After customer returns to the frontend success or failure route, forward the callback query to `GET /api/payments/esewa/success` or `GET /api/payments/esewa/failure`.
7. Use the callback handler response as the first UI payload because the backend already decodes the redirect payload and runs verification/finalization.
8. Only use `GET /api/payments/{orderNumber}/status` or `POST /api/payments/esewa/verify` as processing/recovery fallbacks when the callback route cannot finalize cleanly.

For retry behavior:

1. If payment failed or was cancelled, call `POST /api/payments/esewa/initiate`.
2. Backend may create a fresh payment attempt with a new `transaction_uuid`.
3. Use the latest returned payload only.

## 5. Initiate Endpoint

Route:

- `POST /api/payments/esewa/initiate`

Auth:

- customer auth required

Request body:

```json
{
  "order_number": "ORD-20260402-ABC123"
}
```

Behavior:

- loads the order
- ensures the caller owns the order
- reuses the latest active attempt when safe
- creates a fresh attempt when the previous attempt is terminal like `failed` or `cancelled`
- returns a signed eSewa payload

Use this route when:

- frontend needs to restart payment
- previous attempt failed or was cancelled
- frontend wants the latest backend-approved payload

## 6. Success and Failure Redirects

Routes:

- `GET /api/payments/esewa/success`
- `GET /api/payments/esewa/failure`

Important note:

- These routes are backend callback/return handlers.
- They are not trusted by themselves.
- They only trigger verification/finalization logic.

The success callback can include a Base64 encoded `data` payload from eSewa.
The backend decodes it, checks its signature, and still performs a backend status check before finalizing.

The failure callback is also verified against backend payment status before final state is applied.

Frontend note:

- the frontend success and failure pages should call these backend callback routes directly with `order_number` and `data`
- do not treat eSewa redirect query params as final truth
- if eSewa appends `data` using a second `?`, normalize it before calling the backend callback route

## 7. Verify Endpoint

Route:

- `POST /api/payments/esewa/verify`

Auth:

- authenticated order owner or admin

Request body:

```json
{
  "order_number": "ORD-20260402-ABC123",
  "transaction_uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

Use this route when:

- redirect happened but frontend is unsure of the result
- callback was delayed
- frontend wants an explicit backend verification call

## 8. Status Endpoint

Route:

- `GET /api/payments/{orderNumber}/status`

Auth:

- authenticated order owner or admin

This returns the current backend truth for:

- order payment status
- order status
- latest payment attempt
- verification state
- paid timestamp

Frontend should use this endpoint as the final UI truth.

## 9. Backend Finalization Rules

On verified successful payment:

- payment marked paid
- verification marked verified
- `gateway_reference` stored
- `paid_at` stored
- order payment status becomes paid
- order status advances to paid
- audit log and status history are recorded

On mismatch:

- payment is marked failed with mismatch verification state
- order is not marked paid
- stock is not deducted

On cancelled or failed payment:

- payment is marked cancelled or failed
- order is not marked paid
- frontend can retry using initiate

## 10. Idempotency Behavior

This backend is designed so duplicate gateway/browser callbacks do not duplicate side effects.

Protected side effects:

- payment success state
- paid timestamp
- order status advancement
- order status history creation
- audit log duplication handling

If the same successful callback arrives twice:

- the second call is treated as idempotent
- backend returns the current finalized payment state
- no duplicate payment or order-finalization side effects occur

## 11. Payment Attempt Ledger

Every payment attempt stores:

- `transaction_uuid`
- `gateway_reference`
- `status`
- `verification_status`
- `raw_request_json`
- `raw_response_json`
- `paid_at`

This allows:

- retry support
- debugging gateway issues
- manual reconciliation
- duplicate callback tracing

## 12. eSewa Payload Notes

The current implementation follows the official ePay V2 pattern:

- HMAC SHA-256 signature
- Base64-encoded signature value
- signed fields include:
  - `total_amount`
  - `transaction_uuid`
  - `product_code`

The backend also expects success callback payloads to include a signed response body and still verifies using the backend status-check API.

## 13. Frontend Implementation Checklist

- store `order_number` after checkout
- use only the latest initiation payload returned by backend
- submit exact backend-supplied fields to eSewa
- do not generate signature in frontend
- after redirect, call payment status endpoint
- if unresolved, call verify endpoint
- show retry UI only when backend state allows retry

## 14. Safe Retry Strategy

Recommended UI behavior:

1. Checkout completed, redirect user to eSewa.
2. When user returns, show a temporary “Verifying payment” screen.
3. Poll `GET /api/payments/{orderNumber}/status`.
4. If still unresolved, call `POST /api/payments/esewa/verify`.
5. If backend says `failed` or `cancelled`, show retry button.
6. Retry button calls `POST /api/payments/esewa/initiate`.

## 15. Developer Notes

- Backend owns signature generation.
- Backend owns status verification.
- Backend owns final payment truth.
- Frontend should never assume success from browser redirection alone.
- Treat `order_number` as the main frontend payment tracking key.
