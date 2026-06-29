<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductStatusRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Services\Admin\AdminProductService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminProductController extends Controller
{
    public function __construct(
        private readonly AdminProductService $adminProductService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->adminProductService->paginate());
    }

    public function updateStatus(UpdateProductStatusRequest $request, Product $product): ProductResource
    {
        return new ProductResource(
            $this->adminProductService->updateStatus(
                $request->user(),
                $product,
                ProductStatus::from($request->validated('status'))
            )
        );
    }
}
