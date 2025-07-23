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
        Schema::create('bulk_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('seller_id');
            $table->integer('product_id'); // Assuming a single product per bulk order
            $table->integer('variant_combination_id')->nullable(); // For product variants
            $table->json('variant_option_ids')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_orders');
    }
};
