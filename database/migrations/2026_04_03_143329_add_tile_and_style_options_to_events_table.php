// database/migrations/2024_01_03_000001_add_tile_and_style_options_to_events.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('privacy_policy_url')->nullable()->after('privacy_policy_text');

            $table->string('font_heading')->default('Syne')->after('privacy_policy_url');
            $table->string('font_body')->default('DM Sans')->after('font_heading');

            $table->json('tile_fotobomb_config')->nullable()->after('font_body');
            $table->json('tile_voting_config')->nullable()->after('tile_fotobomb_config');
            $table->json('tile_lottery_config')->nullable()->after('tile_voting_config');
            $table->json('tile_membership_config')->nullable()->after('tile_lottery_config');

            $table->json('lottery_extra_fields')->nullable()->after('tile_membership_config');
            $table->json('membership_extra_fields')->nullable()->after('lottery_extra_fields');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_policy_url',
                'font_heading',
                'font_body',
                'tile_fotobomb_config',
                'tile_voting_config',
                'tile_lottery_config',
                'tile_membership_config',
                'lottery_extra_fields',
                'membership_extra_fields',
            ]);
        });
    }
};
