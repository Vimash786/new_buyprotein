<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'commission_rate',
        'description',
        'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active global commission rate
     */
    public static function getActiveCommissionRate()
    {
        $commission = self::where('is_active', true)->first();
        return $commission ? $commission->commission_rate : 10.00;
    }

    /**
     * Get the active global commission record
     */
    public static function getActiveCommission()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Set all other commissions to inactive when this one is activated
     */
    public function activate()
    {
        // Set all other commissions to inactive
        self::where('id', '!=', $this->id)->update(['is_active' => false]);
        
        // Set this commission to active
        $this->update(['is_active' => true]);
    }
}
