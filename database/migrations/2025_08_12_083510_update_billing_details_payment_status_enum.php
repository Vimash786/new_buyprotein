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
        Schema::table('billing_details', function (Blueprint $table) {
            // Add new column with allowed payment statuses
            $table->string('payment_status_new')->nullable()->after('payment_status');
        });
        
        // Copy existing data to new column, normalizing 'complete' to 'completed'
        DB::statement("UPDATE billing_details SET payment_status_new = CASE 
            WHEN payment_status = 'complete' THEN 'completed'
            ELSE payment_status 
        END");
        
        // Drop old column and rename new one
        Schema::table('billing_details', function (Blueprint $table) {
            $table->dropColumn('payment_status');
            $table->renameColumn('payment_status_new', 'payment_status');
        });
        
        // Add check constraint to enforce enum values (for databases that support it)
        DB::statement("ALTER TABLE billing_details ADD CONSTRAINT check_payment_status CHECK (payment_status IN ('pending', 'partially_completed', 'completed', 'failed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove check constraint first
        DB::statement("ALTER TABLE billing_details DROP CONSTRAINT IF EXISTS check_payment_status");
        
        Schema::table('billing_details', function (Blueprint $table) {
            $table->string('payment_status_old')->nullable()->after('payment_status');
        });
        
        // Copy data back, converting statuses back to original format
        DB::statement("UPDATE billing_details SET payment_status_old = CASE 
            WHEN payment_status = 'completed' THEN 'complete'
            WHEN payment_status = 'partially_completed' THEN 'pending'
            ELSE payment_status 
        END");
        
        Schema::table('billing_details', function (Blueprint $table) {
            $table->dropColumn('payment_status');
            $table->renameColumn('payment_status_old', 'payment_status');
        });
    }
};
