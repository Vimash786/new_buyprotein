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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191);
            $table->string('otp_code', 32);
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->json('user_data')->nullable(); // Store temporary user registration data
            $table->timestamps();
            
            $table->index(['email', 'otp_code']);
            $table->index(['email', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
