<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'blog_id',
        'user_id',
        'parent_id',
        'content',
        'status',
    ];

    protected $casts = [
        'blog_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Get the blog that owns the comment
     */
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Get the user who wrote the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment
     */
    public function parent()
    {
        return $this->belongsTo(BlogComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment
     */
    public function replies()
    {
        return $this->hasMany(BlogComment::class, 'parent_id');
    }

    /**
     * Get approved replies
     */
    public function approvedReplies()
    {
        return $this->hasMany(BlogComment::class, 'parent_id')->where('status', 'approved');
    }

    /**
     * Scope to get only approved comments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get only pending comments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get only rejected comments
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if comment is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if comment is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if comment is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the comment
     */
    public function approve()
    {
        $this->update(['status' => 'approved']);
        return $this;
    }

    /**
     * Reject the comment
     */
    public function reject()
    {
        $this->update(['status' => 'rejected']);
        return $this;
    }
}
