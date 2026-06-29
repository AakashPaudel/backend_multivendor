<?php

namespace App\Http\Resources\Vendor;

use App\Http\Resources\ApiResource;
use App\Http\Resources\Order\VendorOrderResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class VendorSalesReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $orders = $this->resource['orders'] ?? null;

        return [
            'filters' => $this->resource['filters'] ?? [],
            'summary' => $this->resource['summary'] ?? [],
            'orders' => $orders instanceof LengthAwarePaginator
                ? VendorOrderResource::collection($orders)->response()->getData(true)
                : $orders,
        ];
    }
}
