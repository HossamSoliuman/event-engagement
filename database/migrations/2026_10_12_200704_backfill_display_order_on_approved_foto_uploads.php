<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The vidiwall plays approved items as a playlist ordered by `display_order`.
 * Number each event's existing approved items 1..n in approval order so the
 * playlist starts out in the order the old queue would have used.
 */
return new class extends Migration
{
    public function up(): void
    {
        $eventIds = DB::table('foto_uploads')->where('status', 'approved')->distinct()->pluck('event_id');

        foreach ($eventIds as $eventId) {
            $ids = DB::table('foto_uploads')
                ->where('event_id', $eventId)
                ->where('status', 'approved')
                ->orderBy('approved_at')
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $index => $id) {
                DB::table('foto_uploads')->where('id', $id)->update(['display_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
