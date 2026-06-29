<?php

namespace App\Services\Support;

use App\Services\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService extends Service
{
    public function generate(string $value, string $modelClass, string $column = 'slug', ?int $ignoreId = null): string
    {
        /** @var class-string<Model> $modelClass */
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'item';
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($this->exists($modelClass, $column, $slug, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected function exists(string $modelClass, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = $modelClass::query()->where($column, $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
