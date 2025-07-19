<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'content',
        'is_active',
        'meta_title',
        'meta_description',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPES = [
        'about-us' => 'About Us',
        'terms-conditions' => 'Terms & Conditions',
        'shipping-policy' => 'Shipping Policy',
        'privacy-policy' => 'Privacy Policy',
        'return-policy' => 'Return Policy',
    ];

    /**
     * Get the user who last updated this policy.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get policy by type.
     */
    public static function getByType($type)
    {
        return static::where('type', $type)->first();
    }

    /**
     * Get formatted type name.
     */
    public function getFormattedTypeAttribute()
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('-', ' ', $this->type));
    }

    /**
     * Scope to get active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
