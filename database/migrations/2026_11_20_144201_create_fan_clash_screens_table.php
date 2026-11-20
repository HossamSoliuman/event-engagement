<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fan_clash_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('fan_clash_round_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedSmallInteger('round_seconds')->default(20);
            $table->unsignedTinyInteger('countdown_seconds')->default(3);
            $table->boolean('show_taps')->default(true);
            $table->boolean('show_qr')->default(true);
            $table->boolean('confetti')->default(true);

            $table->boolean('on_screen')->default(false);
            $table->boolean('resume_player')->default(false);
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });

        Schema::useNativeSchemaOperationsIfPossible();
        Schema::table('fan_clash_rounds', function (Blueprint $table) {
            $table->dropColumn('screen_cleared_at');
        });
        Schema::useNativeSchemaOperationsIfPossible(false);
    }

    public function down(): void
    {
        Schema::table('fan_clash_rounds', function (Blueprint $table) {
            $table->timestamp('screen_cleared_at')->nullable()->after('finished_at');
        });

        Schema::dropIfExists('fan_clash_screens');
    }
};
