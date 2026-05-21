<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->json('options');
            $table->unsignedTinyInteger('correct_option');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quiz_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['waiting', 'active', 'finished'])->default('waiting');
            $table->unsignedSmallInteger('time_limit_seconds')->default(30);
            $table->unsignedTinyInteger('questions_per_round')->default(3);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_round_id')->constrained()->onDelete('cascade');
            $table->foreignId('quiz_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('session_token', 64);
            $table->string('guest_name');
            $table->unsignedTinyInteger('selected_option');
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('time_taken_ms')->default(0);
            $table->timestamps();

            $table->unique(['quiz_round_id', 'quiz_question_id', 'session_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_rounds');
        Schema::dropIfExists('quiz_questions');
    }
};
