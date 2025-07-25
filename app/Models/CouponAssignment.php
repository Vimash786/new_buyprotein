<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'assignable_type',
        'assignable_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the coupon for this assignment
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the assignable model (User, Product, Seller)
     */
    public function getAssignableAttribute()
    {
        switch ($this->assignable_type) {
            case 'user':
                return $this->user;
            case 'product':
                return $this->product;
            case 'seller':
                return $this->seller;
            case 'all_products':
                return null; // No specific model for all products
            default:
                return null;
        }
    }

    /**
     * Get the user if assignable_type is 'user'
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'assignable_id');
    }

    /**
     * Get the product if assignable_type is 'product'
     */
    public function product()
    {
        return $this->belongsTo(products::class, 'assignable_id');
    }

    /**
     * Get the seller if assignable_type is 'seller'
     */
    public function seller()
    {
        return $this->belongsTo(Sellers::class, 'assignable_id');
    }

    /**
     * Get the user who made this assignment
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Boot method to set assigned_at timestamp
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->assigned_at = now();
        });
    }
}
