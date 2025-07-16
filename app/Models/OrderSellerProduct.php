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
        'unit_price',
        'total_amount',
        'status',
        'notes',
        'variant_combination_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'string',
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
}
