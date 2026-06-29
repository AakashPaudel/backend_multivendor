<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recommendation\RecommendationResource;
use App\Models\Product;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {}

    public function show(Product $product): AnonymousResourceCollection
    {
        return RecommendationResource::collection(
            $this->recommendationService->forProduct($product)
        );
    }
}
