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
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->string('subtitle')->nullable()->after('title');
            $table->text('description')->nullable()->after('subtitle');
            $table->string('button_text')->default('Shop Now')->after('description');
            $table->string('button_link')->nullable()->after('button_text');
            $table->string('text_color')->default('#ffffff')->after('button_link');
            $table->string('position')->default('primary')->after('text_color'); // primary, secondary, promotional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'subtitle', 
                'description',
                'button_text',
                'button_link',
                'text_color',
                'position'
            ]);
        });
    }
};
