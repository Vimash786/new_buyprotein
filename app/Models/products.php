<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

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
        'gym_owner_price',
        'regular_user_price',
        'shop_owner_price',
        'gym_owner_discount',
        'regular_user_discount',
        'shop_owner_discount',
        'gym_owner_final_price',
        'regular_user_final_price',
        'shop_owner_final_price',
        'stock_quantity',
        'weight',
        'category',
        'old_category',
        'status',
        'super_status',
        'section_category',
        'thumbnail_image',
        'has_variants',
    ];

    protected $casts = [
        'seller_id' => 'integer',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',

        'gym_owner_price' => 'decimal:2',
        'regular_user_price' => 'decimal:2',
        'shop_owner_price' => 'decimal:2',
        'gym_owner_discount' => 'decimal:2',
        'regular_user_discount' => 'decimal:2',
        'shop_owner_discount' => 'decimal:2',
        'gym_owner_final_price' => 'decimal:2',
        'regular_user_final_price' => 'decimal:2',
        'shop_owner_final_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'status' => 'string',
        'super_status' => 'string',
        'has_variants' => 'boolean',
        'section_category' => 'array',
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
     * Get the discount percentage based on user role and variant status.
     */
    public function getDiscountPercentageAttribute()
    {
        $user = Auth::user();
        $userRole = $user ? $user->role : 'User';
        
        // Check if product has variants
        $variant = $this->variantCombinations->first();
        
        if ($variant) {
            // Product has variants, use variant discount
            return match ($userRole) {
                'User' => $variant->regular_user_discount ?? 0,
                'Gym Owner/Trainer/Influencer/Dietitian' => $variant->gym_owner_discount ?? 0,
                'Shop Owner' => $variant->shop_owner_discount ?? 0,
                default => $variant->regular_user_discount ?? 0
            };
        } else {
            // Product without variants, use product discount
            return match ($userRole) {
                'User' => $this->regular_user_discount ?? 0,
                'Gym Owner/Trainer/Influencer/Dietitian' => $this->gym_owner_discount ?? 0,
                'Shop Owner' => $this->shop_owner_discount ?? 0,
                default => $this->regular_user_discount ?? 0
            };
        }
    }

    /**
     * Get formatted section category.
     */
    public function getSectionCategoryDisplayAttribute()
    {
        $categories = is_array($this->section_category) ? $this->section_category : [$this->section_category];

        $displayNames = array_map(function ($category) {
            return match ($category) {
                'everyday_essential' => 'Everyday Essential',
                'popular_pick' => 'Popular Pick',
                'exclusive_deal' => 'Exclusive Deal & Offers',
                default => 'Everyday Essential'
            };
        }, $categories);

        return implode(', ', $displayNames);
    }

    /**
     * Get all order items for this product.
     */
    public function orderSellerProducts(): HasMany
    {
        return $this->hasMany(OrderSellerProduct::class, 'product_id');
    }
}
