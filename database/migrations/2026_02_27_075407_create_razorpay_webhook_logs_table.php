<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('razorpay_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->nullable();
            $table->string('event')->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->text('error_description')->nullable();
            $table->string('error_code')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_webhook_logs');
    }
};
