<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorOrderStatusRequest;
use App\Http\Resources\Order\VendorOrderResource;
use App\Models\VendorOrder;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', VendorOrder::class);

        return VendorOrderResource::collection(
            $this->orderService->paginateVendorOrders($request->user())
        );
    }

    public function show(VendorOrder $vendorOrder): VendorOrderResource
    {
        $this->authorize('view', $vendorOrder);

        return new VendorOrderResource(
            $this->orderService->detailVendorOrder($vendorOrder)
        );
    }

    public function updateStatus(UpdateVendorOrderStatusRequest $request, VendorOrder $vendorOrder): VendorOrderResource
    {
        $this->authorize('update', $vendorOrder);

        return new VendorOrderResource(
            $this->orderService->updateVendorOrderStatus($request->user(), $vendorOrder, $request->validated())
        );
    }
}
