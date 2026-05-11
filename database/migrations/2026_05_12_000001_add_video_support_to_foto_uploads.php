<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foto_uploads', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('thumbnail_path');
            $table->enum('media_type', ['photo', 'video'])->default('photo')->after('video_path');
            $table->float('video_duration')->nullable()->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('foto_uploads', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'media_type', 'video_duration']);
        });
    }
};
