<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('event_id');
            $table->boolean('allow_multiple')->default(false)->after('options');
        });

        DB::table('survey_questions')
            ->orderBy('event_id')
            ->orderBy('id')
            ->get(['id', 'event_id'])
            ->groupBy('event_id')
            ->each(function ($questions) {
                foreach ($questions->values() as $index => $question) {
                    DB::table('survey_questions')->where('id', $question->id)->update(['position' => $index + 1]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn(['position', 'allow_multiple']);
        });
    }
};
