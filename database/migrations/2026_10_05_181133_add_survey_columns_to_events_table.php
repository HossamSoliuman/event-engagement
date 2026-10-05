<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('module_survey')->default(false)->after('module_fanclash');
            $table->string('survey_title')->default('Fan Survey')->after('fanclash_title');
            $table->string('survey_desc')->nullable()->after('fanclash_desc');
            $table->json('tile_survey_config')->nullable()->after('tile_fanclash_config');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['module_survey', 'survey_title', 'survey_desc', 'tile_survey_config']);
        });
    }
};
