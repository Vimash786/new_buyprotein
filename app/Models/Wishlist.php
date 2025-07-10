<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'variant_option_ids',
        'price',
        'quantity',
        'total',
    ];

    protected $casts = [
        'variant_option_ids' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(products::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_option_ids');
    }
}
