<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fan_clash_matchups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable();
            $table->string('side_a_name');
            $table->string('side_b_name');
            $table->string('side_a_color', 7)->nullable();
            $table->string('side_b_color', 7)->nullable();
            $table->string('side_a_image_path')->nullable();
            $table->string('side_b_image_path')->nullable();
            $table->string('sponsor_logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fan_clash_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('fan_clash_matchup_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('side_a_name');
            $table->string('side_b_name');
            $table->string('side_a_color', 7);
            $table->string('side_b_color', 7);
            $table->string('side_a_image_path')->nullable();
            $table->string('side_b_image_path')->nullable();
            $table->string('sponsor_logo_path')->nullable();
            $table->enum('status', ['waiting', 'active', 'finished'])->default('waiting');
            $table->unsignedSmallInteger('duration_seconds')->default(20);
            $table->unsignedBigInteger('side_a_taps')->default(0);
            $table->unsignedBigInteger('side_b_taps')->default(0);
            $table->enum('winner_side', ['a', 'b', 'tie'])->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fan_clash_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fan_clash_round_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('session_token', 64);
            $table->enum('side', ['a', 'b']);
            $table->unsignedInteger('taps')->default(0);
            $table->timestamps();

            $table->unique(['fan_clash_round_id', 'session_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fan_clash_participants');
        Schema::dropIfExists('fan_clash_rounds');
        Schema::dropIfExists('fan_clash_matchups');
    }
};
