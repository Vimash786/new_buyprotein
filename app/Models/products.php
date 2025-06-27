<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class products extends Model
{
    /** @use HasFactory<\Database\Factories\ProductsFactory> */
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'sub_category_id',
        'name',
        'description',
        'price',
        'stock_quantity',
        'category',
        'old_category',
        'brand',
        'status',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the seller that owns the product.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Sellers::class, 'seller_id');
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the sub-category that owns the product.
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /**
     * Get the orders for the product.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(orders::class, 'product_id');
    }
}
