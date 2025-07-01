<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'value',
        'display_value',
        'price_adjustment',
        'stock_quantity',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'product_variant_id' => 'integer',
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the variant that owns the option.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the display value or fallback to value.
     */
    public function getDisplayValueAttribute($value)
    {
        return $value ?: $this->attributes['value'];
    }
}
