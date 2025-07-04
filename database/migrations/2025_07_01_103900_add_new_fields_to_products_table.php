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
            $table->enum('section_category', ['everyday_essential', 'popular_pick', 'exclusive_deal'])
                  ->default('everyday_essential')
                  ->after('sub_category_id');
            $table->string('thumbnail_image')->nullable()->after('stock_quantity');
            $table->integer('discount_percentage')->default(0)->after('price');
            $table->decimal('discounted_price', 10, 2)->nullable()->after('discount_percentage');
            $table->boolean('has_variants')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'section_category',
                'thumbnail_image',
                'discount_percentage',
                'discounted_price',
                'has_variants'
            ]);
        });
    }
};
