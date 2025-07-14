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
        // Use raw SQL to safely drop constraints and columns
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Drop foreign key constraint if it exists
        $constraintExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'product_images' 
            AND CONSTRAINT_NAME = 'product_images_variant_combination_id_foreign'
            AND TABLE_SCHEMA = DATABASE()
        ");
        
        if (!empty($constraintExists)) {
            DB::statement('ALTER TABLE product_images DROP FOREIGN KEY product_images_variant_combination_id_foreign');
        }
        
        // Drop index if it exists
        $indexExists = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_NAME = 'product_images' 
            AND INDEX_NAME = 'product_images_product_id_variant_combination_id_index'
            AND TABLE_SCHEMA = DATABASE()
        ");
        
        if (!empty($indexExists)) {
            DB::statement('ALTER TABLE product_images DROP INDEX product_images_product_id_variant_combination_id_index');
        }
        
        // Drop columns if they exist
        Schema::table('product_images', function (Blueprint $table) {
            $columnsToCheck = ['variant_combination_id', 'file_size', 'image_type'];
            $columnsToDrop = [];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('product_images', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
