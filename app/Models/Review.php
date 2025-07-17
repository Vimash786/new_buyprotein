<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'product_reviews';

    protected $fillable = ['user_id', 'product_id', 'name', 'email', 'review', 'rating'];
}
