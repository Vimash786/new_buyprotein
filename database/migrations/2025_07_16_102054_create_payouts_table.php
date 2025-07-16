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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_id')->unique();
            $table->unsignedBigInteger('seller_id');
            $table->string('seller_name');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('payout_amount', 10, 2)->default(0);
            $table->date('due_date');
            $table->date('payout_date');
            $table->enum('payment_status', ['paid', 'unpaid', 'processing', 'cancelled'])->default('unpaid');
            $table->date('period_start');
            $table->date('period_end');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            $table->index(['payment_status', 'due_date']);
            $table->index(['seller_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
