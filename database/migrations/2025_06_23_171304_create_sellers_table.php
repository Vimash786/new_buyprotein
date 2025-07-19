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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('gst_number')->unique();
            $table->json('product_category');
            $table->string('contact_no');
            $table->string('brand')->nullable();
            $table->string('brand_logo')->nullable();
            $table->string('brand_certificate')->nullable();
            $table->enum('status', ['approved', 'not_approved'])->default('not_approved');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
