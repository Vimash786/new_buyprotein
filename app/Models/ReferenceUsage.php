<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceUsage extends Model
{
    use HasFactory;
    protected $table = 'reference_usage'; // Explicitly set the table name
    protected $fillable = [
        'reference_id',
        'user_id',
        'order_id',
        'discount_amount',
        'order_total',
        'used_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'order_total' => 'decimal:2',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the reference for this usage
     */
    public function reference()
    {
        return $this->belongsTo(reference::class);
    }

    /**
     * Get the user who used this reference
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order this reference was used on
     */
    public function order()
    {
        return $this->belongsTo(orders::class, 'order_id');
    }

    /**
     * Boot method to set used_at timestamp
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->used_at = now();
        });
    }
}
