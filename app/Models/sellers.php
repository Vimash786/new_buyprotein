<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Sellers extends Model
{
    /** @use HasFactory<\Database\Factories\SellersFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'gst_number',
        'product_category',
        'contact_person',
        'brand_certificate',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the user that owns the seller profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the seller.
     */
    public function products(): HasMany
    {
        return $this->hasMany(products::class, 'seller_id');
    }

    /**
     * Get all orders through products.
     */
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(orders::class, products::class, 'seller_id', 'product_id');
    }
}
