<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'product_id',
        'variant_combination_id',
        'variant_option_ids',
        'quantity',
    ];

    protected $casts = [
        'variant_option_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Sellers::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(products::class, 'product_id');
    }

    public function variantCombination()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_combination_id');
    }

    public function products()
    {
        // Get all products from the same seller
        return $this->hasMany(products::class, 'seller_id', 'seller_id');
    }

    /**
     * Get formatted variant text for display
     */
    public function getVariantDisplayText()
    {
        // First try to use variant combination relationship if available
        if ($this->variantCombination) {
            return $this->variantCombination->getFormattedVariantText();
        }

        // Fallback to using variant_option_ids directly
        if (!$this->variant_option_ids || empty($this->variant_option_ids)) {
            return null;
        }

        try {
            // Get variant options from ProductVariantOption model using the IDs
            $options = \App\Models\ProductVariantOption::whereIn('id', $this->variant_option_ids)
                ->with('variant')
                ->get();

            if ($options->isEmpty()) {
                return 'No options found for IDs: ' . implode(', ', $this->variant_option_ids);
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