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
        Schema::table('reference_usage', function (Blueprint $table) {
            // Add order_total and used_at columns if they don't exist
            if (!Schema::hasColumn('reference_usage', 'order_total')) {
                $table->decimal('order_total', 10, 2)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('reference_usage', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('order_total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_usage', function (Blueprint $table) {
            if (Schema::hasColumn('reference_usage', 'order_total')) {
                $table->dropColumn('order_total');
            }
            if (Schema::hasColumn('reference_usage', 'used_at')) {
                $table->dropColumn('used_at');
            }
        });
    }
};
