<?php

namespace App\Services\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminProductService extends Service
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function paginate(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['vendor.vendorProfile', 'category', 'images'])
            ->latest('id')
            ->paginate();
    }

    public function updateStatus(User $actor, Product $product, ProductStatus $status): Product
    {
        return DB::transaction(function () use ($actor, $product, $status): Product {
            $product->forceFill([
                'status' => $status,
            ])->save();

            $this->auditLogService->record($actor, 'product.status_changed', $product, [
                'status' => $status->value,
            ]);

            return $product->fresh(['vendor.vendorProfile', 'category', 'images']);
        });
    }
}
