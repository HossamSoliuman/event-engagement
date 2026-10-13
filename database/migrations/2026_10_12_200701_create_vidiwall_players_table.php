<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vidiwall_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('state', 10)->default('stopped');
            $table->foreignId('foto_upload_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('started_ms')->nullable();
            $table->unsignedInteger('paused_elapsed_ms')->nullable();
            $table->boolean('loop')->default(false);
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vidiwall_players');
    }
};
