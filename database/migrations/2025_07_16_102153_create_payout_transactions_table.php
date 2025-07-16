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
        Schema::create('payout_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->unsignedBigInteger('payout_id');
            $table->enum('payment_method', ['bank_transfer', 'upi', 'wallet']);
            $table->datetime('transaction_date');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('bank_details')->nullable();
            $table->json('upi_details')->nullable();
            $table->json('wallet_details')->nullable();
            $table->enum('status', ['completed', 'pending', 'failed', 'cancelled'])->default('pending');
            $table->timestamps();
            
            $table->foreign('payout_id')->references('id')->on('payouts')->onDelete('cascade');
            $table->index(['payout_id', 'status']);
            $table->index(['payment_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_transactions');
    }
};
