<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSellerProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'product_id',
        'quantity',
        'variant',
        'unit_price',
        'total_amount',
        'status',
        'product_payment_status',
        'notes',
        'variant_combination_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'string',
        'product_payment_status' => 'string',
        'variant' => 'array',
    ];

    /**
     * Get the order that owns this item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(orders::class, 'order_id');
    }

    /**
     * Get the seller for this item.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Sellers::class, 'seller_id');
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(products::class, 'product_id');
    }

    /**
     * Get the variant combination for this item.
     */
    public function variantCombination(): BelongsTo
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_combination_id');
    }
    public function variantCombinationImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'variant_combination_id');
    }

    /**
     * Get formatted variant text for display purposes.
     */
    public function getFormattedVariantText()
    {
        // If variant_combination_id exists, use it for consistency
        if ($this->variant_combination_id) {
            $variantCombination = $this->variantCombination;
            return $variantCombination ? $variantCombination->getFormattedVariantText() : null;
        }

        // Fallback to using the variant field (array of option IDs)
        if (!$this->variant || !is_array($this->variant)) {
            return null;
        }

        try {
            $options = \App\Models\ProductVariantOption::whereIn('id', $this->variant)
                ->with('variant')
                ->get();

            if ($options->isEmpty()) {
                return null;
            }

            $variantText = [];
            foreach ($options->groupBy('variant.name') as $variantName => $variantOptions) {
                foreach ($variantOptions as $option) {
                    $variantText[] = ucfirst($variantName) . ': ' . ($option->display_value ?? $option->value);
                }
            }

            return implode(', ', $variantText);
        } catch (\Exception $e) {
            return 'Error loading variant: ' . $e->getMessage();
        }
    }
    public function getFormattedVariantImg()
    {
        // If variant_combination_id exists, use it for consistency
        if ($this->variant_combination_id) {
            $variantCombination = $this->variantCombination;
            return $variantCombination ? $variantCombination->getFormattedVariantText() : null;
        }

        // Fallback to using the variant field (array of option IDs)
        if (!$this->variant || !is_array($this->variant)) {
            return null;
        }

        try {
            $options = \App\Models\ProductImage::whereIn('id', $this->variant)
                ->with('variant')
                ->get();

            if ($options->isEmpty()) {
                return null;
            }

            $variantText = [];
            foreach ($options->groupBy('variant.name') as $variantName => $variantOptions) {
                foreach ($variantOptions as $option) {
                    $variantText[] = ucfirst($variantName) . ': ' . ($option->display_value ?? $option->value);
                }
            }

            return implode(', ', $variantText);
        } catch (\Exception $e) {
            return 'Error loading variant: ' . $e->getMessage();
        }
    }
}
