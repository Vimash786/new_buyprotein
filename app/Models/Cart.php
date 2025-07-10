<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'variant_option_ids',
        'quantity',
        'price'
    ];

    protected $casts = [
        'variant_option_ids' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(products::class);
    }
}
