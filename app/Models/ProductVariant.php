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
        'color_name',
        'size_name',
        'age_label',
        'price',
        'compare_price',
        'quantity_on_hand',
        'quantity_reserved',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
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

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }

}
