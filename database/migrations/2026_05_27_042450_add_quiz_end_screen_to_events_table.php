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
            $table->text('quiz_winner_text')->nullable()->after('quiz_desc');
            $table->string('quiz_end_sponsor_logo_path')->nullable()->after('quiz_winner_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['quiz_winner_text', 'quiz_end_sponsor_logo_path']);
        });
    }
};
