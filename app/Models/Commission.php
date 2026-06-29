<?php

namespace App\Models;

use App\Enums\CommissionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'vendor_id',
        'rate',
        'active_from',
        'active_to',
    ];

    protected function casts(): array
    {
        return [
            'scope' => CommissionScope::class,
            'rate' => 'decimal:2',
            'active_from' => 'datetime',
            'active_to' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
