<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_id',
        'seller_id',
        'seller_name',
        'total_orders',
        'total_sales',
        'commission_amount',
        'payout_amount',
        'due_date',
        'payout_date',
        'payment_status',
        'period_start',
        'period_end',
        'notes',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'total_sales' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'due_date' => 'date',
        'payout_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_status' => 'string',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payout) {
            // Generate unique payout ID
            $payout->payout_id = 'PO' . date('Y') . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the seller that owns the payout.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Sellers::class, 'seller_id');
    }

    /**
     * Get all transactions for this payout.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PayoutTransaction::class, 'payout_id');
    }

    /**
     * Scope to filter by payment status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope to filter by due soon (within 5 days).
     */
    public function scopeDueSoon($query)
    {
        return $query->where('due_date', '<=', Carbon::now()->addDays(5))
                    ->where('payment_status', 'unpaid');
    }

    /**
     * Scope to filter by overdue.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::now())
                    ->where('payment_status', 'unpaid');
    }

    /**
     * Check if payout is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === 'unpaid' && $this->due_date < Carbon::now();
    }

    /**
     * Check if payout is due soon (within 5 days).
     */
    public function isDueSoon(): bool
    {
        return $this->payment_status === 'unpaid' && 
               $this->due_date <= Carbon::now()->addDays(5) &&
               $this->due_date >= Carbon::now();
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'paid' => 'green',
            'unpaid' => $this->isOverdue() ? 'red' : 'yellow',
            'processing' => 'blue',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get formatted status text.
     */
    public function getStatusTextAttribute(): string
    {
        $status = ucfirst($this->payment_status);
        
        if ($this->payment_status === 'unpaid' && $this->isOverdue()) {
            return $status . ' (Overdue)';
        }
        
        if ($this->payment_status === 'unpaid' && $this->isDueSoon()) {
            return $status . ' (Due Soon)';
        }
        
        return $status;
    }

    /**
     * Calculate commission percentage.
     */
    public function getCommissionPercentageAttribute(): float
    {
        if ($this->total_sales > 0) {
            return ($this->commission_amount / $this->total_sales) * 100;
        }
        return 0;
    }

    /**
     * Get the period display string.
     */
    public function getPeriodDisplayAttribute(): string
    {
        return $this->period_start->format('M d') . ' - ' . $this->period_end->format('M d, Y');
    }
}
