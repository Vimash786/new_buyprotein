<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'payout_id',
        'payment_method',
        'transaction_date',
        'amount',
        'notes',
        'reference_number',
        'bank_details',
        'upi_details',
        'wallet_details',
        'status',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'bank_details' => 'array',
        'upi_details' => 'array',
        'wallet_details' => 'array',
        'status' => 'string',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Generate unique transaction ID
            $transaction->transaction_id = 'TXN' . date('Y') . str_pad(static::count() + 1, 8, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the payout that owns this transaction.
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class, 'payout_id');
    }

    /**
     * Get payment method display name.
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'upi' => 'UPI',
            'wallet' => 'Wallet',
            default => ucfirst(str_replace('_', ' ', $this->payment_method))
        };
    }

    /**
     * Get payment method icon.
     */
    public function getPaymentMethodIconAttribute(): string
    {
        return match($this->payment_method) {
            'bank_transfer' => 'building-library',
            'upi' => 'device-phone-mobile',
            'wallet' => 'wallet',
            default => 'banknotes'
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }
}
