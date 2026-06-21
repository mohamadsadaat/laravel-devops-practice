<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
   protected $fillable = [
        'product_id',
        'sku',
        'age_label',
        'quantity_on_hand',
        'quantity_reserved',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'available_quantity',
    ];

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity_on_hand - (int) $this->quantity_reserved);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }

}
