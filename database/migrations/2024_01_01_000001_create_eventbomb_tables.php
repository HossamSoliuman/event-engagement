<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('sponsor_logo_path')->nullable();
            $table->string('primary_color', 7)->default('#FF3D00');
            $table->string('secondary_color', 7)->default('#1A1A2E');
            $table->string('accent_color', 7)->default('#FFD700');
            $table->boolean('is_active')->default(true);
            $table->boolean('module_fotobomb')->default(true);
            $table->boolean('module_lottery')->default(true);
            $table->boolean('module_voting')->default(true);
            $table->boolean('module_membership')->default(true);
            $table->string('fotobomb_title')->default('Foto Bomb');
            $table->string('lottery_title')->default('Win Today');
            $table->string('voting_title')->default('Athlete of the Day');
            $table->string('membership_title')->default('Become a Member');
            $table->json('voting_options')->nullable();
            $table->timestamps();
        });

        Schema::create('foto_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('uploader_name')->nullable();
            $table->string('uploader_phone')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('on_screen')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('displayed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('lottery_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name');
            $table->string('voter_session')->nullable();
            $table->string('voter_ip')->nullable();
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('team_preference')->nullable();
            $table->boolean('newsletter_opt_in')->default(false);
            $table->timestamps();
        });

        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('session_token', 64)->unique();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('lottery_entries');
        Schema::dropIfExists('foto_uploads');
        Schema::dropIfExists('events');
    }
};