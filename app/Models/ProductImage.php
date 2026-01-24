<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_combination_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
        'file_size',
        'image_type',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_combination_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(products::class, 'product_id');
    }

    /**
     * Get the variant combination that owns the image.
     */
    public function variantCombination(): BelongsTo
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_combination_id');
    }

    /**
     * Get the full URL for the image.
     */
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if image is within size limits (200KB to 400KB).
     */
    public function isValidSize(): bool
    {
        if (!$this->file_size) {
            return false;
        }
        
        $minSize = 200 * 1024; // 200KB in bytes
        $maxSize = 400 * 1024; // 400KB in bytes
        
        return $this->file_size >= $minSize && $this->file_size <= $maxSize;
    }

    /**
     * Scope for product images only (not variant-specific).
     */
    public function scopeProductOnly($query)
    {
        return $query->where('image_type', 'product')->whereNull('variant_combination_id');
    }

    /**
     * Scope for variant images only.
     */
    public function scopeVariantOnly($query)
    {
        return $query->where('image_type', 'variant')->whereNotNull('variant_combination_id');
    }

    /**
     * Scope for variant thumbnails only.
     */
    public function scopeVariantThumbnailOnly($query)
    {
        return $query->where('image_type', 'variant_thumbnail')->whereNotNull('variant_combination_id');
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Delete the image file when the ProductImage record is deleted
        static::deleting(function ($productImage) {
            if ($productImage->image_path && Storage::disk('public')->exists($productImage->image_path)) {
                Storage::disk('public')->delete($productImage->image_path);
            }
        });
    }
}
