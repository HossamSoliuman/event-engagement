<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('module_fanclash')->default(false)->after('module_quiz');
            $table->string('fanclash_title')->default('Fan Clash')->after('quiz_title');
            $table->string('fanclash_desc')->nullable()->after('quiz_desc');
            $table->json('tile_fanclash_config')->nullable()->after('tile_quiz_config');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['module_fanclash', 'fanclash_title', 'fanclash_desc', 'tile_fanclash_config']);
        });
    }
};
