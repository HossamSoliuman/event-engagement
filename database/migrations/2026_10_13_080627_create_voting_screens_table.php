<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voting_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('headline', 60)->nullable();
            $table->unsignedTinyInteger('top_count')->default(0);
            $table->boolean('show_votes')->default(true);
            $table->boolean('show_percent')->default(true);
            $table->boolean('show_total')->default(true);
            $table->boolean('show_position')->default(true);
            $table->boolean('show_qr')->default(true);
            $table->unsignedTinyInteger('countdown_seconds')->default(5);
            $table->unsignedSmallInteger('result_seconds')->default(0);
            $table->boolean('confetti')->default(true);
            $table->boolean('close_on_reveal')->default(true);

            $table->string('show_type', 10)->nullable();
            $table->unsignedBigInteger('show_starts_ms')->nullable();
            $table->unsignedInteger('show_countdown_ms')->default(0);
            $table->unsignedInteger('show_result_ms')->default(0);
            $table->json('show_standings')->nullable();
            $table->boolean('resume_player')->default(false);
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_screens');
    }
};
