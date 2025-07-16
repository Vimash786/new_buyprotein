<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantCombination extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_options',
        'sku',
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
        'is_active',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_options' => 'array',
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
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the variant combination.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(products::class, 'product_id');
    }

    /**
     * Get the images for this variant combination.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'variant_combination_id');
    }

    /**
     * Get the primary image for this variant combination.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class, 'variant_combination_id')->where('is_primary', true);
    }

    /**
     * Get the thumbnail image for this variant combination.
     */
    public function thumbnailImage()
    {
        return $this->hasOne(ProductImage::class, 'variant_combination_id')->where('image_type', 'variant_thumbnail');
    }

    /**
     * Get the variant options for this combination.
     */
    public function getVariantOptionsDetails()
    {
        $optionIds = $this->variant_options;
        if (empty($optionIds)) {
            return collect();
        }

        return ProductVariantOption::whereIn('id', $optionIds)
            ->with('variant')
            ->get()
            ->groupBy('variant.name');
    }

    /**
     * Generate SKU based on product and variant options.
     */
    public function generateSku()
    {
        $product = $this->product;
        $options = $this->getVariantOptionsDetails();
        
        $skuParts = [
            strtoupper(substr($product->name, 0, 3)),
            $product->id
        ];
        
        foreach ($options as $variantName => $variantOptions) {
            foreach ($variantOptions as $option) {
                $skuParts[] = strtoupper(substr($option->value, 0, 3));
            }
        }
        
        return implode('-', $skuParts);
    }

    /**
     * Calculate final price for gym owner based on discount.
     */
    public function calculateGymOwnerFinalPrice()
    {
        if ($this->gym_owner_discount > 0 && $this->gym_owner_price > 0) {
            return $this->gym_owner_price * (1 - ($this->gym_owner_discount / 100));
        }
        return $this->gym_owner_price;
    }

    /**
     * Calculate final price for regular user based on discount.
     */
    public function calculateRegularUserFinalPrice()
    {
        if ($this->regular_user_discount > 0 && $this->regular_user_price > 0) {
            return $this->regular_user_price * (1 - ($this->regular_user_discount / 100));
        }
        return $this->regular_user_price;
    }

    /**
     * Calculate final price for shop owner based on discount.
     */
    public function calculateShopOwnerFinalPrice()
    {
        if ($this->shop_owner_discount > 0 && $this->shop_owner_price > 0) {
            return $this->shop_owner_price * (1 - ($this->shop_owner_discount / 100));
        }
        return $this->shop_owner_price;
    }

    /**
     * Auto-calculate and update all final prices.
     */
    public function updateFinalPrices()
    {
        $this->gym_owner_final_price = $this->calculateGymOwnerFinalPrice();
        $this->regular_user_final_price = $this->calculateRegularUserFinalPrice();
        $this->shop_owner_final_price = $this->calculateShopOwnerFinalPrice();
    }

    /**
     * Get formatted variant text for display
     */
    public function getFormattedVariantText()
    {
        $options = $this->getVariantOptionsDetails();
        if ($options->isEmpty()) {
            return $this->sku ?? 'Variant';
        }

        $variantText = [];
        foreach ($options as $variantName => $variantOptions) {
            foreach ($variantOptions as $option) {
                $variantText[] = ucfirst($variantName) . ': ' . $option->value;
            }
        }

        return implode(', ', $variantText);
    }
}
