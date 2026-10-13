<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('winner_rank')->nullable()->after('won_at');
            $table->string('prize', 120)->nullable()->after('winner_rank');
        });

        DB::table('lottery_entries')->where('is_winner', true)->update(['winner_rank' => 1]);
    }

    public function down(): void
    {
        Schema::table('lottery_entries', function (Blueprint $table) {
            $table->dropColumn(['winner_rank', 'prize']);
        });
    }
};
