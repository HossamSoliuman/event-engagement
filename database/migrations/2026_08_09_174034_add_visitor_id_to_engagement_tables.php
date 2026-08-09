<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every guest-facing module previously tracked identity its own way (or not
     * at all), which made unique-participant and engagement-rate reporting
     * impossible. A single visitor_id, issued as a cookie on the landing page,
     * gives all modules one shared key to aggregate on.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'foto_uploads',
        'votes',
        'lottery_entries',
        'memberships',
        'quiz_answers',
        'fan_clash_participants',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->string('visitor_id', 32)->nullable()->after('event_id');
                $blueprint->index(['event_id', 'visitor_id'], "{$table}_event_visitor_index");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex("{$table}_event_visitor_index");
                $blueprint->dropColumn('visitor_id');
            });
        }
    }
};
