<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'display_name',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(products::class, 'product_id');
    }

    /**
     * Get the variant options for the variant.
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class, 'product_variant_id')->orderBy('sort_order');
    }

    /**
     * Get active variant options for the variant.
     */
    public function activeOptions(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class, 'product_variant_id')->where('is_active', true)->orderBy('sort_order');
    }
}
