<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'status',
        'section_category',
        'thumbnail_image',
        'discount_percentage',
        'discounted_price',
        'has_variants',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'status' => 'string',
        'discount_percentage' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'has_variants' => 'boolean',
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

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * Get the variant combinations for the product.
     */
    public function variantCombinations(): HasMany
    {
        return $this->hasMany(ProductVariantCombination::class, 'product_id');
    }

    /**
     * Get active variant combinations for the product.
     */
    public function activeVariantCombinations(): HasMany
    {
        return $this->hasMany(ProductVariantCombination::class, 'product_id')->where('is_active', true);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')->where('is_primary', true);
    }

    /**
     * Get the final price after discount.
     */
    public function getFinalPriceAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price * (1 - ($this->discount_percentage / 100));
        }
        return $this->discounted_price ?: $this->price;
    }

    /**
     * Get the savings amount.
     */
    public function getSavingsAttribute()
    {
        if ($this->discount_percentage > 0) {
            return $this->price * ($this->discount_percentage / 100);
        }
        return $this->price - ($this->discounted_price ?: $this->price);
    }

    /**
     * Check if product has discount.
     */
    public function getHasDiscountAttribute()
    {
        return $this->discount_percentage > 0 || $this->discounted_price < $this->price;
    }

    /**
     * Get formatted section category.
     */
    public function getSectionCategoryDisplayAttribute()
    {
        return match($this->section_category) {
            'everyday_essential' => 'Everyday Essential',
            'popular_pick' => 'Popular Pick',
            'exclusive_deal' => 'Exclusive Deal & Offers',
            default => 'Everyday Essential'
        };
    }
}
