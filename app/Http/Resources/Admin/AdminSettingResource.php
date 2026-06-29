<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class AdminSettingResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $updatedAt = is_array($this->resource)
            ? ($this->resource['updated_at'] ?? null)
            : $this->updated_at?->toISOString();

        return [
            'key' => is_array($this->resource) ? $this->resource['key'] : $this->key,
            'value' => is_array($this->resource) ? $this->resource['value'] : $this->value,
            'updated_at' => $updatedAt,
        ];
    }
}
