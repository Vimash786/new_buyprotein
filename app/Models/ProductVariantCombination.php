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
        'price',
        'discount_percentage',
        'discounted_price',
        'stock_quantity',
        'is_active',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_options' => 'array',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discounted_price' => 'decimal:2',
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
}
