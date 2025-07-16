<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sellers extends Model
{
    /** @use HasFactory<\Database\Factories\SellersFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'gst_number',
        'product_category',
        'contact_person',
        'commission',
        'brand',
        'brand_logo',
        'brand_certificate',
        'status',
    ];

    protected $casts = [
        'product_category' => 'array',
        'status' => 'string',
    ];

    /**
     * Get the user that owns the seller profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the seller.
     */
    public function products(): HasMany
    {
        return $this->hasMany(products::class, 'seller_id');
    }

    /**
     * Get all order items for this seller.
     */
    public function orderSellerProducts(): HasMany
    {
        return $this->hasMany(OrderSellerProduct::class, 'seller_id');
    }

    /**
     * Get all orders through order-seller-products.
     */
    public function orders()
    {
        return $this->belongsToMany(orders::class, 'order_seller_products', 'seller_id', 'order_id')
                    ->withPivot(['product_id', 'quantity', 'unit_price', 'total_amount', 'status', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get orders with specific status for this seller.
     */
    public function getOrdersByStatus($status)
    {
        return $this->orderSellerProducts()
                    ->where('status', $status)
                    ->with(['order', 'product'])
                    ->get();
    }

    /**
     * Get total sales amount for this seller.
     */
    public function getTotalSales()
    {
        return $this->orderSellerProducts()
                    ->whereIn('status', ['delivered', 'completed'])
                    ->sum('total_amount');
    }

    /**
     * Get pending orders for this seller.
     */
    public function getPendingOrders()
    {
        return $this->orderSellerProducts()
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->with(['order', 'product'])
                    ->get();
    }
}
