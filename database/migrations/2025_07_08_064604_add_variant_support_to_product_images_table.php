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
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_combination_id')->nullable()->after('product_id');
            $table->integer('file_size')->nullable()->after('image_path'); // Store file size in bytes
            $table->string('image_type')->default('product')->after('file_size'); // 'product' or 'variant'
            
            $table->foreign('variant_combination_id')->references('id')->on('product_variant_combinations')->onDelete('cascade');
            $table->index(['product_id', 'variant_combination_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['variant_combination_id']);
            $table->dropIndex(['product_id', 'variant_combination_id']);
            $table->dropColumn(['variant_combination_id', 'file_size', 'image_type']);
        });
    }
};
