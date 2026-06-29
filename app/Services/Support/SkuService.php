<?php

namespace App\Services\Support;

use App\Models\Product;
use App\Services\Service;
use Illuminate\Support\Str;

class SkuService extends Service
{
    public function generate(string $productName, ?int $ignoreId = null): string
    {
        $normalized = Str::upper(Str::of($productName)->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '')->substr(0, 6));
        $prefix = $normalized !== '' ? str_pad($normalized, 6, 'X') : 'PRODUC';

        $suffix = 1;

        do {
            $sku = sprintf('%s-%04d', $prefix, $suffix);
            $suffix++;
        } while ($this->exists($sku, $ignoreId));

        return $sku;
    }

    protected function exists(string $sku, ?int $ignoreId): bool
    {
        $query = Product::query()->where('sku', $sku);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
