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
        Schema::table('users', function (Blueprint $table) {
            // null = not required (Regular User / Shop Owner)
            // pending = waiting for admin approval (Gym Owner/Trainer/Influencer/Dietitian)
            // approved = approved by admin
            // rejected = rejected by admin
            $table->string('approval_status')->nullable()->after('business_images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });
    }
};
