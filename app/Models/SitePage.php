<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SitePage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'page_type',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Page types constants
    const PAGE_TYPES = [
        'about-us' => 'About Us',
        'terms-conditions' => 'Terms & Conditions',
        'shipping-policy' => 'Shipping Policy',
        'privacy-policy' => 'Privacy Policy',
        'return-policy' => 'Return Policy'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('page_type', $type);
    }

    // Helper methods
    public static function getPageByType($type)
    {
        return static::active()->byType($type)->first();
    }

    public static function getPageContent($type)
    {
        $page = static::getPageByType($type);
        return $page ? $page->content : null;
    }

    // Accessors
    public function getPageTypeNameAttribute()
    {
        return self::PAGE_TYPES[$this->page_type] ?? $this->page_type;
    }

    public function getStatusTextAttribute()
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    // Mutators
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = $value ?: Str::slug($this->title);
    }
}
