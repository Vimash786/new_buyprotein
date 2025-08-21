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

    /**
     * Get the variant combination that matches the stored variant option IDs
     */
    public function getVariantCombination()
    {
        if (!$this->variant_option_ids || !$this->product) {
            return null;
        }

        $selectedOptionIds = array_values($this->variant_option_ids);
        sort($selectedOptionIds); // Sort for consistent comparison

        foreach ($this->product->variantCombinations as $combination) {
            $combinationOptionIds = is_array($combination->variant_options) 
                ? $combination->variant_options 
                : json_decode($combination->variant_options, true);
            
            if ($combinationOptionIds) {
                sort($combinationOptionIds);
                if ($selectedOptionIds === $combinationOptionIds) {
                    return $combination;
                }
            }
        }

        return null;
    }

    /**
     * Get the variant image for this wishlist item
     */
    public function getVariantImage()
    {
        $combination = $this->getVariantCombination();
        
        if (!$combination) {
            return null;
        }

        return $this->product->images
            ->where('variant_combination_id', $combination->id)
            ->first();
    }
}
