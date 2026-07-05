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
        Schema::table('events', function (Blueprint $table) {
            $table->string('landing_style')->default('classic')->after('font_body');
            $table->string('landing_hero_title')->nullable()->after('landing_style');
            $table->string('landing_hero_sub')->nullable()->after('landing_hero_title');
            $table->json('vidiwall_frame_config')->nullable()->after('vidiwall_overlay_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'landing_style',
                'landing_hero_title',
                'landing_hero_sub',
                'vidiwall_frame_config',
            ]);
        });
    }
};
