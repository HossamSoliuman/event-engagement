<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uploads moved out of foto_uploads to start an event's playlist over.
     * Rows keep their original id and columns so they can be put back.
     */
    public function up(): void
    {
        Schema::create('archived_foto_uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 32)->nullable();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('media_type')->default('photo');
            $table->float('video_duration')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('uploader_name')->nullable();
            $table->string('uploader_phone')->nullable();
            $table->string('uploader_session')->nullable();
            $table->string('status');
            $table->boolean('on_screen')->default(false);
            $table->integer('display_order')->default(0);
            $table->text('admin_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('displayed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_foto_uploads');
    }
};
