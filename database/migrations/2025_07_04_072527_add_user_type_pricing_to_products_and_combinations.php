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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('gym_owner_price', 10, 2)->nullable()->after('section_category');
            $table->decimal('regular_user_price', 10, 2)->nullable()->after('gym_owner_price');
            $table->decimal('shop_owner_price', 10, 2)->nullable()->after('regular_user_price');

            // Add user type discount fields
            $table->decimal('gym_owner_discount', 5, 2)->default(0)->after('shop_owner_price');
            $table->decimal('regular_user_discount', 5, 2)->default(0)->after('gym_owner_discount');
            $table->decimal('shop_owner_discount', 5, 2)->default(0)->after('regular_user_discount');
            
            // Add user type final price fields
            $table->decimal('gym_owner_final_price', 10, 2)->nullable()->after('shop_owner_discount');
            $table->decimal('regular_user_final_price', 10, 2)->nullable()->after('gym_owner_final_price');
            $table->decimal('shop_owner_final_price', 10, 2)->nullable()->after('regular_user_final_price');
        });
        
        Schema::table('product_variant_combinations', function (Blueprint $table) {
            $table->decimal('gym_owner_price', 10, 2)->nullable()->after('sku');
            $table->decimal('regular_user_price', 10, 2)->nullable()->after('gym_owner_price');
            $table->decimal('shop_owner_price', 10, 2)->nullable()->after('regular_user_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gym_owner_price', 'regular_user_price', 'shop_owner_price']);
        });
        
        Schema::table('product_variant_combinations', function (Blueprint $table) {
            $table->dropColumn(['gym_owner_price', 'regular_user_price', 'shop_owner_price']);
        });
    }
};
