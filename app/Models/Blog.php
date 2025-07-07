<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'meta_title',
        'meta_description',
        'tags',
        'is_featured',
        'status',
        'published_at',
        'views_count',
        'likes_count',
        'comments_count',
        'author_id',
        'category_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });

        static::updating(function ($blog) {
            if ($blog->isDirty('title') && empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }

    /**
     * Scope to get only published blogs
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope to get only draft blogs
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get only featured blogs
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get blogs by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to search blogs
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Get the blog's author
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the blog's category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the blog's comments
     */
    public function comments()
    {
        return $this->hasMany(BlogComment::class);
    }

    /**
     * Get approved comments
     */
    public function approvedComments()
    {
        return $this->hasMany(BlogComment::class)->where('status', 'approved');
    }

    /**
     * Get the blog's likes
     */
    public function likes()
    {
        return $this->hasMany(BlogLike::class);
    }

    /**
     * Get the featured image URL
     */
    public function getFeaturedImageUrlAttribute()
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }
        return null;
    }

    /**
     * Get the blog's URL
     */
    public function getUrlAttribute()
    {
        return route('blog.show', $this->slug);
    }

    /**
     * Get reading time estimate
     */
    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / 200); // Average reading speed
        return $readingTime . ' min read';
    }

    /**
     * Check if blog is published
     */
    public function isPublished()
    {
        return $this->status === 'published' && 
               $this->published_at && 
               $this->published_at <= now();
    }

    /**
     * Check if blog is draft
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if blog is featured
     */
    public function isFeatured()
    {
        return $this->is_featured;
    }

    /**
     * Publish the blog
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        return $this;
    }

    /**
     * Unpublish the blog
     */
    public function unpublish()
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        return $this;
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
        return $this;
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured()
    {
        $this->update(['is_featured' => !$this->is_featured]);
        return $this;
    }
}
