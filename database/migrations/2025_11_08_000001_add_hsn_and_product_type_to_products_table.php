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
		Schema::table('products', function (Blueprint $table) {
			if (!Schema::hasColumn('products', 'hsn')) {
				$table->string('hsn')->nullable()->after('weight');
			}
			if (!Schema::hasColumn('products', 'product_type')) {
				$table->string('product_type')->nullable()->after('hsn');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('products', function (Blueprint $table) {
			if (Schema::hasColumn('products', 'product_type')) {
				$table->dropColumn('product_type');
			}
			if (Schema::hasColumn('products', 'hsn')) {
				$table->dropColumn('hsn');
			}
		});
	}
};


