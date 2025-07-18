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
        Schema::create('global_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default Commission');
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert the default commission record
        DB::table('global_commissions')->insert([
            'name' => 'Default Commission',
            'commission_rate' => 10.00,
            'description' => 'Default commission rate for new sellers',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_commissions');
    }
};
