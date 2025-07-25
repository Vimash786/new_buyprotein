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
        Schema::create('reference_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id')->constrained('reference')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('order_total', 10, 2)->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            // Add index for order_id for performance
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_usage');
    }
};
