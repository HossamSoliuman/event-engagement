<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('fanclash_bg_color', 7)->nullable()->after('fanclash_desc');
            $table->string('fanclash_accent_color', 7)->nullable()->after('fanclash_bg_color');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['fanclash_bg_color', 'fanclash_accent_color']);
        });
    }
};
