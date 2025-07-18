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
        'next_payout_date',
    ];

    protected $casts = [
        'product_category' => 'array',
        'status' => 'string',
        'next_payout_date' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::updated(function ($seller) {
            // When a seller is approved, set their next payout date if not already set
            if ($seller->isDirty('status') && $seller->status === 'approved' && !$seller->next_payout_date) {
                $seller->next_payout_date = now()->addDays(15);
                $seller->saveQuietly(); // Save without triggering events again
            }
        });
    }

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
                    ->whereIn('status', ['delivered'])
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

    /**
     * Get all payouts for this seller.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'seller_id');
    }

    /**
     * Get the latest payout for this seller.
     */
    public function latestPayout()
    {
        return $this->hasOne(Payout::class, 'seller_id')->latest();
    }

    /**
     * Get pending payouts for this seller.
     */
    public function pendingPayouts()
    {
        return $this->hasMany(Payout::class, 'seller_id')->where('payment_status', 'unpaid');
    }

    /**
     * Calculate total earnings for a period.
     */
    public function calculateEarnings($startDate, $endDate)
    {
        $orderItems = $this->orderSellerProducts()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered'])
            ->get();

        $totalOrders = $orderItems->count();
        $totalSales = $orderItems->sum('total_amount');
        $commissionRate = floatval($this->commission);
        $commissionAmount = $totalSales * ($commissionRate / 100);
        $payoutAmount = $totalSales - $commissionAmount;

        return [
            'total_orders' => $totalOrders,
            'total_sales' => $totalSales,
            'commission_amount' => $commissionAmount,
            'payout_amount' => $payoutAmount,
            'commission_rate' => $commissionRate
        ];
    }
}
