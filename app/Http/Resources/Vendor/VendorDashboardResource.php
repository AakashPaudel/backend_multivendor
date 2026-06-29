<?php

namespace App\Http\Resources\Vendor;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class VendorDashboardResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
