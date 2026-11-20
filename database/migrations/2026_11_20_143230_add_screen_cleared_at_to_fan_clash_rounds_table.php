<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fan_clash_rounds', function (Blueprint $table) {
            $table->timestamp('screen_cleared_at')->nullable()->after('finished_at');
        });

        DB::table('fan_clash_rounds')
            ->where('status', 'finished')
            ->update(['screen_cleared_at' => DB::raw('COALESCE(finished_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        Schema::table('fan_clash_rounds', function (Blueprint $table) {
            $table->dropColumn('screen_cleared_at');
        });
    }
};
