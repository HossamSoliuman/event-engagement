<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('module_quiz')->default(false)->after('module_membership');
            // $table->string('quiz_title')->default('Quiz to Win')->after('membership_title');
            $table->string('quiz_desc')->nullable()->after('membership_desc');
            $table->json('tile_quiz_config')->nullable()->after('tile_membership_config');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['module_quiz', 'quiz_title', 'quiz_desc', 'tile_quiz_config']);
        });
    }
};
