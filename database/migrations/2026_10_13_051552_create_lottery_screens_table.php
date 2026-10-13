<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('draw_on_screen')->default(false);
            $table->unsignedTinyInteger('countdown_seconds')->default(5);
            $table->unsignedTinyInteger('drawing_seconds')->default(6);
            $table->unsignedSmallInteger('result_seconds')->default(30);
            $table->string('headline', 60)->nullable();
            $table->string('name_format', 10)->default('full');
            $table->boolean('show_prize')->default(true);
            $table->boolean('show_phone')->default(false);
            $table->boolean('show_entry_count')->default(true);
            $table->boolean('confetti')->default(true);

            $table->string('show_type', 10)->nullable();
            $table->foreignId('lottery_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('show_starts_ms')->nullable();
            $table->unsignedInteger('show_countdown_ms')->default(0);
            $table->unsignedInteger('show_drawing_ms')->default(0);
            $table->unsignedInteger('show_result_ms')->default(0);
            $table->json('show_reel')->nullable();
            $table->boolean('resume_player')->default(false);
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_screens');
    }
};
