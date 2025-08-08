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
        Schema::table('billing_details', function (Blueprint $table) {
            $table->string('billing_first_name')->nullable()->after('order_id');
            $table->string('billing_last_name')->nullable()->after('billing_first_name');
            $table->decimal('item_price', 10, 2)->nullable()->after('total_amount');
            $table->decimal('gst_amount', 10, 2)->nullable()->after('item_price');
            $table->decimal('total_before_discount', 10, 2)->nullable()->after('gst_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_details', function (Blueprint $table) {
            $table->dropColumn([
                'billing_first_name', 
                'billing_last_name',
                'item_price',
                'gst_amount', 
                'total_before_discount'
            ]);
        });
    }
};
