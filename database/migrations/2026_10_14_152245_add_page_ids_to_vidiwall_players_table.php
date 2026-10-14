<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vidiwall_players', function (Blueprint $table) {
            $table->json('page_ids')->nullable()->after('foto_upload_id');
        });
    }

    public function down(): void
    {
        Schema::table('vidiwall_players', function (Blueprint $table) {
            $table->dropColumn('page_ids');
        });
    }
};
