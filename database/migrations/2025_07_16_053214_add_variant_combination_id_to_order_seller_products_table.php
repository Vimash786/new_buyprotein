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
        Schema::table('order_seller_products', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_combination_id')->nullable()->after('product_id');
            $table->foreign('variant_combination_id')->references('id')->on('product_variant_combinations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_seller_products', function (Blueprint $table) {
            $table->dropForeign(['variant_combination_id']);
            $table->dropColumn('variant_combination_id');
        });
    }
};
