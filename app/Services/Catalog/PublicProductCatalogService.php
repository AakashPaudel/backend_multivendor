<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PublicProductCatalogService extends Service
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'images', 'vendor.vendorProfile'])
            ->visible();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? null);

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    public function findVisibleBySlug(string $slug): Product
    {
        return Product::query()
            ->with(['category', 'images', 'vendor.vendorProfile'])
            ->visible()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'images', 'vendor.vendorProfile'])
            ->visible();

        if (($filters['q'] ?? null) !== null && $filters['q'] !== '') {
            $keyword = trim($filters['q']);

            $query->where(function (Builder $searchQuery) use ($keyword): void {
                $searchQuery
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('short_description', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('vendor.vendorProfile', fn (Builder $vendorQuery) => $vendorQuery->where('store_name', 'like', "%{$keyword}%"));
            });
        }

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? null);

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (($filters['category'] ?? null) !== null && $filters['category'] !== '') {
            $category = $filters['category'];

            $query->whereHas('category', function (Builder $categoryQuery) use ($category): void {
                if (is_numeric($category)) {
                    $categoryQuery->whereKey((int) $category);
                } else {
                    $categoryQuery->where('slug', $category);
                }
            });
        }

        if (($filters['vendor'] ?? null) !== null && $filters['vendor'] !== '') {
            $vendor = $filters['vendor'];

            $query->whereHas('vendor.vendorProfile', function (Builder $vendorQuery) use ($vendor): void {
                if (is_numeric($vendor)) {
                    $vendorQuery->whereKey((int) $vendor);
                } else {
                    $vendorQuery->where('slug', $vendor);
                }
            });
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
    }

    protected function applySorting(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popularity' => $query->withCount('orderItems')->orderByDesc('order_items_count')->orderByDesc('id'),
            default => $query->latest('id'),
        };
    }
}
