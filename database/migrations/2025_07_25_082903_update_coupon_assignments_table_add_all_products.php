<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupon_assignments', function (Blueprint $table) {
            // Drop the old enum column and recreate with new values
            DB::statement("ALTER TABLE coupon_assignments MODIFY COLUMN assignable_type ENUM('product', 'user', 'seller', 'user_type', 'all_products')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_assignments', function (Blueprint $table) {
            // Revert back to original enum values
            DB::statement("ALTER TABLE coupon_assignments MODIFY COLUMN assignable_type ENUM('product', 'user', 'seller', 'user_type')");
        });
    }
};
