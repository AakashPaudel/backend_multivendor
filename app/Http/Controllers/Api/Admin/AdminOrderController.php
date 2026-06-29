<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\Order\OrderDetailResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        return OrderResource::collection(
            $this->orderService->paginateForAdmin()
        );
    }

    public function show(Order $order): OrderDetailResource
    {
        $this->authorize('view', $order);

        return new OrderDetailResource(
            $this->orderService->detailForAdmin($order)
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderDetailResource
    {
        $this->authorize('update', $order);

        return new OrderDetailResource(
            $this->orderService->updateOrderStatus($request->user(), $order, $request->validated())
        );
    }
}
