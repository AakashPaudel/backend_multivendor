<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class AdminDashboardResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
