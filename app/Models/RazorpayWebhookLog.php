<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayWebhookLog extends Model
{
    protected $fillable = [
        'payment_id',
        'event',
        'email',
        'contact',
        'error_description',
        'error_code',
        'payload'
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
