<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps()
            ->withPivot(['collection_id', 'product_id']);
    }

    public function activeProducts(): BelongsToMany
    {
        return $this->products()->where('status', 'active');
    }

    public function featuredProducts(): BelongsToMany
    {
        return $this->products()->where('is_featured', true);
    }

    public function productsCount()
    {
        return $this->hasOne('App\Models\CollectionProduct')
            ->selectRaw('collection_id, count(*) as aggregate')
            ->groupBy('collection_id');
    }

    public function getProductsCountAttribute()
    {
        if (!$this->relationLoaded('productsCount')) {
            $this->load('productsCount');
        }

        $related = $this->getRelation('productsCount');

        return $related ? $related->aggregate : 0;
    }
}
