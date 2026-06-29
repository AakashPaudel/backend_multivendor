<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class AdminReportResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
