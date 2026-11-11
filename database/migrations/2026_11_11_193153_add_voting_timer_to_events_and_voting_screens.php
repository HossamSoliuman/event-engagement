<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('voting_ends_at')->nullable()->after('voting_closed');
        });

        Schema::table('voting_screens', function (Blueprint $table) {
            $table->unsignedTinyInteger('voting_minutes')->default(3)->after('event_id');
            $table->string('stage', 16)->default('ready')->after('voting_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('voting_ends_at');
        });

        Schema::table('voting_screens', function (Blueprint $table) {
            $table->dropColumn(['voting_minutes', 'stage']);
        });
    }
};
