<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address',
        'gst_number',
        'subtotal',
        'tax_amount',
        'shipping_charge',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the order that owns this billing detail.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(orders::class, 'order_id');
    }
}
