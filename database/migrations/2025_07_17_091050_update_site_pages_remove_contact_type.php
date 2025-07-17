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
        // First, delete any existing contact pages
        DB::table('site_pages')->where('page_type', 'contact')->delete();
        
        // Then update the enum to remove contact type
        DB::statement("ALTER TABLE site_pages MODIFY COLUMN page_type ENUM('about-us', 'terms-conditions', 'shipping-policy', 'privacy-policy', 'return-policy') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the enum to include contact type
        DB::statement("ALTER TABLE site_pages MODIFY COLUMN page_type ENUM('about-us', 'terms-conditions', 'shipping-policy', 'privacy-policy', 'return-policy', 'contact') NOT NULL");
    }
};
