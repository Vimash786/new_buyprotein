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
            $table->foreignId('category_id')->nullable()->after('seller_id')->constrained('categories')->onDelete('set null');
            $table->foreignId('sub_category_id')->nullable()->after('category_id')->constrained('sub_categories')->onDelete('set null');
            
            // Change existing category column to old_category for migration purposes and make it nullable
            $table->renameColumn('category', 'old_category');
        });
        
        // Make old_category nullable in a separate statement after rename
        Schema::table('products', function (Blueprint $table) {
            $table->string('old_category')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['sub_category_id']);
            $table->dropColumn(['category_id', 'sub_category_id']);
            $table->renameColumn('old_category', 'category');
        });
    }
};
