<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionProduct extends Model
{
    protected $table = 'collection_product';
    
    protected $fillable = [
        'collection_id',
        'product_id',
    ];

    public $timestamps = true;
}
