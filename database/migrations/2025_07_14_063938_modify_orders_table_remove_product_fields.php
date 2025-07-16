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
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key and product-specific columns
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'product_id',
                'quantity',
                'unit_price',
                'total_amount',
                'notes'
            ]);
            
            // Add new fields for the main order
            $table->string('order_number')->unique()->after('id');
            $table->enum('overall_status', ['pending', 'processing', 'partially_shipped', 'completed', 'cancelled'])->default('pending')->after('user_id');
            $table->decimal('total_order_amount', 10, 2)->default(0)->after('overall_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Restore the original structure
            $table->dropColumn(['order_number', 'overall_status', 'total_order_amount']);
            
            // Add back the original columns
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
        });
    }
};
