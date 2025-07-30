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
        Schema::table('reference', function (Blueprint $table) {
            // Rename existing 'value' field to 'giver_discount'
            $table->renameColumn('value', 'giver_discount');
            
            // Add new applyer_discount field
            $table->decimal('applyer_discount', 10, 2)->after('giver_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference', function (Blueprint $table) {
            // Remove applyer_discount field
            $table->dropColumn('applyer_discount');
            
            // Rename giver_discount back to value
            $table->renameColumn('giver_discount', 'value');
        });
    }
};
