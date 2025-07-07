<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'user_id',
    ];

    protected $casts = [
        'blog_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the blog that owns the like
     */
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Get the user who liked the blog
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
