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
            // Add separate fields for giver earnings and applyer discounts
            $table->decimal('giver_earning_amount', 10, 2)->after('discount_amount')->default(0);
            $table->decimal('applyer_discount_amount', 10, 2)->after('giver_earning_amount')->default(0);
            $table->foreignId('giver_user_id')->nullable()->after('applyer_discount_amount')->constrained('users')->onDelete('set null');
            
            // Rename discount_amount to total_discount_amount for clarity
            $table->renameColumn('discount_amount', 'total_discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_usage', function (Blueprint $table) {
            // Remove the new fields
            $table->dropForeign(['giver_user_id']);
            $table->dropColumn(['giver_earning_amount', 'applyer_discount_amount', 'giver_user_id']);
            
            // Rename back to original
            $table->renameColumn('total_discount_amount', 'discount_amount');
        });
    }
};
