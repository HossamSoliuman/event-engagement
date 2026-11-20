<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::useNativeSchemaOperationsIfPossible();
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('vidiwall_show_uploader');
        });
        Schema::useNativeSchemaOperationsIfPossible(false);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('vidiwall_show_uploader')->default(true)->after('lottery_winner_id');
        });
    }
};
