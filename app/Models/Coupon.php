<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type', // 'percentage', 'fixed'
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'used_count',
        'user_usage_limit',
        'starts_at',
        'expires_at',
        'status', // 'active', 'inactive'
        'applicable_to', // 'all', 'users', 'products', 'sellers'
        'user_types', // JSON array of user types that can use this coupon
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'user_usage_limit' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'user_types' => 'array',
        'status' => 'string',
        'applicable_to' => 'string',
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to get only active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now());
    }

    /**
     * Scope to get expired coupons
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope to get upcoming coupons
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * Get the user who created this coupon
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this coupon
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get coupon assignments to users
     */
    public function userAssignments()
    {
        return $this->hasMany(CouponAssignment::class)->where('assignable_type', 'user');
    }

    /**
     * Get coupon assignments to products
     */
    public function productAssignments()
    {
        return $this->hasMany(CouponAssignment::class)->where('assignable_type', 'product');
    }

    /**
     * Get coupon assignments to sellers
     */
    public function sellerAssignments()
    {
        return $this->hasMany(CouponAssignment::class)->where('assignable_type', 'seller');
    }

    /**
     * Check if coupon is assigned to all products
     */
    public function isAssignedToAllProducts()
    {
        return $this->hasMany(CouponAssignment::class)->where('assignable_type', 'all_products')->exists();
    }

    /**
     * Get all assignments for this coupon
     */
    public function assignments()
    {
        return $this->hasMany(CouponAssignment::class);
    }

    /**
     * Get coupon usage records
     */
    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is currently valid
     */
    public function isValid()
    {
        return $this->status === 'active' &&
               $this->starts_at <= now() &&
               $this->expires_at >= now() &&
               ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    /**
     * Check if coupon is expired
     */
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    /**
     * Check if coupon is upcoming
     */
    public function isUpcoming()
    {
        return $this->starts_at > now();
    }

    /**
     * Check if coupon has reached usage limit
     */
    public function hasReachedLimit()
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Calculate discount amount for given total
     */
    public function calculateDiscount($total)
    {
        if ($this->minimum_amount && $total < $this->minimum_amount) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = ($total * $this->value) / 100;
            
            if ($this->maximum_discount) {
                $discount = min($discount, $this->maximum_discount);
            }
            
            return $discount;
        }

        if ($this->type === 'fixed') {
            return min($this->value, $total);
        }

        return 0;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        if ($this->isExpired()) {
            return 'red';
        }
        
        if ($this->isUpcoming()) {
            return 'yellow';
        }
        
        if ($this->status === 'active') {
            return 'green';
        }
        
        return 'gray';
    }

    /**
     * Get human readable status
     */
    public function getHumanStatusAttribute()
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        if ($this->isUpcoming()) {
            return 'Upcoming';
        }
        
        return ucfirst($this->status);
    }

    /**
     * Assign coupon to a model (User, Product, Seller)
     */
    public function assignTo($model, $assignedBy = null)
    {
        $assignableType = $this->getAssignableType($model);
        
        return CouponAssignment::create([
            'coupon_id' => $this->id,
            'assignable_type' => $assignableType,
            'assignable_id' => $model->id,
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Remove assignment from a model
     */
    public function removeAssignmentFrom($model)
    {
        $assignableType = $this->getAssignableType($model);
        
        return CouponAssignment::where([
            'coupon_id' => $this->id,
            'assignable_type' => $assignableType,
            'assignable_id' => $model->id,
        ])->delete();
    }

    /**
     * Check if coupon is assigned to a model
     */
    public function isAssignedTo($model)
    {
        $assignableType = $this->getAssignableType($model);
        
        return CouponAssignment::where([
            'coupon_id' => $this->id,
            'assignable_type' => $assignableType,
            'assignable_id' => $model->id,
        ])->exists();
    }

    /**
     * Get the correct assignable type enum value for a model
     */
    private function getAssignableType($model)
    {
        $className = get_class($model);
        
        switch ($className) {
            case 'App\Models\User':
                return 'user';
            case 'App\Models\products':
                return 'product';
            case 'App\Models\Sellers':
                return 'seller';
            case 'all_products':
                return 'all_products';
            default:
                throw new \InvalidArgumentException("Unsupported model type: {$className}");
        }
    }
}
