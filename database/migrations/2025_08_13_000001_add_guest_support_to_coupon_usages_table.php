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
        Schema::table('coupon_usages', function (Blueprint $table) {
            // Make user_id nullable to support guest users
            $table->foreignId('user_id')->nullable()->change();
            
            // Add guest identifier for tracking guest user coupon usage
            $table->string('guest_identifier')->nullable()->after('user_id');
            
            // Add order total for better tracking
            $table->decimal('order_total', 10, 2)->nullable()->after('discount_amount');
            
            // Add used_at timestamp
            $table->timestamp('used_at')->nullable()->after('order_total');
            
            // Add index for guest_identifier for performance
            $table->index('guest_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropIndex(['guest_identifier']);
            $table->dropColumn(['guest_identifier', 'order_total', 'used_at']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
