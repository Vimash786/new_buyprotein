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
        // First, modify the column type to text to accommodate JSON
        Schema::table('products', function (Blueprint $table) {
            $table->text('section_category')->change();
        });
        
        // Then update existing data to convert enum values to JSON arrays
        DB::statement("UPDATE products SET section_category = CONCAT('[\"', section_category, '\"]')");
        
        // Finally, change to JSON type with default
        Schema::table('products', function (Blueprint $table) {
            $table->json('section_category')->default('["everyday_essential"]')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON arrays back to single enum values (take first value)
        DB::statement("UPDATE products SET section_category = JSON_UNQUOTE(JSON_EXTRACT(section_category, '$[0]'))");
        
        Schema::table('products', function (Blueprint $table) {
            $table->enum('section_category', ['everyday_essential', 'popular_pick', 'exclusive_deal'])
                  ->default('everyday_essential')
                  ->change();
        });
    }
};
