<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The vidiwall now plays every approved item once, in approval order, and uses
 * a null `displayed_at` as "still waiting". Mark the pre-existing approved
 * backlog as already shown so it does not replay the moment this ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('foto_uploads')
            ->where('status', 'approved')
            ->whereNull('displayed_at')
            ->update(['displayed_at' => DB::raw('COALESCE(approved_at, created_at)')]);
    }

    public function down(): void
    {
        //
    }
};
