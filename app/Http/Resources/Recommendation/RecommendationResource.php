<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\ApiResource;
use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\Request;

class RecommendationResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $product = is_array($this->resource)
            ? ($this->resource['product'] ?? null)
            : null;

        return [
            'product_id' => is_array($this->resource) ? $this->resource['product_id'] : $this->product_id,
            'recommended_product_id' => is_array($this->resource) ? $this->resource['recommended_product_id'] : $this->recommended_product_id,
            'support_value' => is_array($this->resource) ? $this->resource['support_value'] : $this->support_value,
            'confidence_value' => is_array($this->resource) ? $this->resource['confidence_value'] : $this->confidence_value,
            'lift_value' => is_array($this->resource) ? $this->resource['lift_value'] : $this->lift_value,
            'generated_at' => is_array($this->resource) ? $this->resource['generated_at'] : $this->generated_at?->toISOString(),
            'product' => $product ?? $this->whenLoaded('recommendedProduct', fn () => new ProductResource($this->recommendedProduct)),
        ];
    }
}
