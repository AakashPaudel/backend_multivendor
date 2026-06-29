<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderDetailResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\InvoiceService;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        return OrderResource::collection(
            $this->orderService->paginateForCustomer($request->user())
        );
    }

    public function show(Order $order): OrderDetailResource
    {
        $this->authorize('view', $order);

        return new OrderDetailResource(
            $this->orderService->detailForCustomer($order)
        );
    }

    public function invoice(Order $order): StreamedResponse
    {
        $this->authorize('view', $order);

        return $this->invoiceService->response($order);
    }
}
