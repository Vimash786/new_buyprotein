<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class orders extends Model
{
    /** @use HasFactory<\Database\Factories\OrdersFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_email',
        'order_number',
        'overall_status',
        'total_order_amount',
        'status',
    ];

    protected $casts = [
        'total_order_amount' => 'decimal:2',
        'overall_status' => 'string',
        'status' => 'string',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all order items (seller products) for this order.
     */
    public function orderSellerProducts(): HasMany
    {
        return $this->hasMany(OrderSellerProduct::class, 'order_id');
    }

    /**
     * Get the billing details for this order.
     */
    public function billingDetail(): HasOne
    {
        return $this->hasOne(BillingDetail::class, 'order_id');
    }

    /**
     * Get the shipping address for this order.
     */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(ShippingAddress::class, 'order_id');
    }

    /**
     * Get all sellers associated with this order.
     */
    public function sellers()
    {
        return $this->belongsToMany(Sellers::class, 'order_seller_products', 'order_id', 'seller_id')
            ->withPivot(['product_id', 'quantity', 'unit_price', 'total_amount', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all products associated with this order.
     */
    public function products()
    {
        return $this->belongsToMany(products::class, 'order_seller_products', 'order_id', 'product_id')
            ->withPivot(['seller_id', 'quantity', 'unit_price', 'total_amount', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get order items for a specific seller.
     */
    public function getSellerOrderItems($sellerId)
    {
        return $this->orderSellerProducts()->where('seller_id', $sellerId)->get();
    }

    /**
     * Calculate the total amount for a specific seller in this order.
     */
    public function getSellerTotal($sellerId)
    {
        return $this->orderSellerProducts()
            ->where('seller_id', $sellerId)
            ->sum('total_amount');
    }
}
