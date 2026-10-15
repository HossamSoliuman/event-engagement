<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vidiwall_players', function (Blueprint $table) {
            $table->boolean('qr_screen')->default(false)->after('loop');
            $table->boolean('resume_after_qr')->default(false)->after('qr_screen');
        });
    }

    public function down(): void
    {
        Schema::table('vidiwall_players', function (Blueprint $table) {
            $table->dropColumn(['qr_screen', 'resume_after_qr']);
        });
    }
};
