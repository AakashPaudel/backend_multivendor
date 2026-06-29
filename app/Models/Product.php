<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\UserStatus;
use App\Enums\VendorApprovalStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'discount_price',
        'stock_quantity',
        'status',
        'thumbnail_path',
        'weight',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'status' => ProductStatus::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ProductStatus::Active)
            ->where('stock_quantity', '>', 0)
            ->whereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->where('status', UserStatus::Active))
            ->whereHas('vendor.vendorProfile', fn (Builder $vendorQuery) => $vendorQuery->where('approval_status', VendorApprovalStatus::Approved));
    }

    public function scopeLowStock(Builder $query, int $threshold = 5): Builder
    {
        return $query->where('stock_quantity', '<=', $threshold);
    }

    public function isSellable(): bool
    {
        return $this->status === ProductStatus::Active
            && $this->stock_quantity > 0
            && $this->vendor?->status === UserStatus::Active
            && $this->vendor?->vendorProfile?->approval_status === VendorApprovalStatus::Approved;
    }

    public function currentUnitPrice(): float
    {
        return (float) ($this->discount_price ?? $this->price);
    }
}
