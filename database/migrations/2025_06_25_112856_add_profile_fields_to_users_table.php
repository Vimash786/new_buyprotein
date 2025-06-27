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
            $table->string('role')->default('User')->after('email');
            $table->boolean('profile_completed')->default(false)->after('role');
            $table->string('document_proof')->nullable()->after('profile_completed');
            $table->string('social_media_link')->nullable()->after('document_proof');
            $table->json('business_images')->nullable()->after('social_media_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'profile_completed']);
        });
    }
};
