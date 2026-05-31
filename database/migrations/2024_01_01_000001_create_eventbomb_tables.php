<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Events ───────────────────────────────────────────────────────────
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('sponsor_logo_path')->nullable();
            $table->string('background_image_path')->nullable();
            $table->string('primary_color', 7)->default('#FF3D00');
            $table->string('secondary_color', 7)->default('#0D0D1A');
            $table->string('accent_color', 7)->default('#FFD700');
            $table->boolean('is_active')->default(true);
            // Module toggles
            $table->boolean('module_fotobomb')->default(true);
            $table->boolean('module_lottery')->default(true);
            $table->boolean('module_voting')->default(true);
            $table->boolean('module_membership')->default(true);
            // Module titles
            $table->string('fotobomb_title')->default('Foto Bomb');
            $table->string('lottery_title')->default('Win Today');
            $table->string('voting_title')->default('Athlete of the Day');
            $table->string('membership_title')->default('Become a Member');
            // Module descriptions
            $table->string('fotobomb_desc')->nullable()->default('Snap a photo and get it on the big screen');
            $table->string('lottery_desc')->nullable()->default('Enter the draw for a chance to win tonight prize');
            $table->string('voting_desc')->nullable()->default('Vote for your Athlete of the Day. Results shown live');
            $table->string('membership_desc')->nullable()->default('Join the community and get exclusive updates.');
            // Voting
            $table->json('voting_options')->nullable();
            $table->boolean('voting_closed')->default(false);
            // Lottery
            $table->boolean('lottery_drawn')->default(false);
            $table->foreignId('lottery_winner_id')->nullable();

            // Vidiwall settings
            $table->boolean('vidiwall_show_uploader')->default(true);
            $table->boolean('vidiwall_slideshow_mode')->default(false);
            $table->integer('vidiwall_slideshow_interval')->default(8); // seconds
            $table->string('vidiwall_overlay_text')->nullable();
            // Timestamps
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Foto Uploads ─────────────────────────────────────────────────────
        Schema::create('foto_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('uploader_name')->nullable();
            $table->string('uploader_phone')->nullable();
            $table->string('uploader_session')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('on_screen')->default(false);
            $table->integer('display_order')->default(0);
            $table->text('admin_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('displayed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Lottery Entries ───────────────────────────────────────────────────
        Schema::create('lottery_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamp('won_at')->nullable();
            $table->string('entry_token', 32)->unique();
            $table->timestamps();
        });

        // ── Votes ─────────────────────────────────────────────────────────────
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name');
            $table->string('candidate_slug');
            $table->string('voter_session')->nullable();
            $table->string('voter_ip')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'voter_session']);
        });

        // ── Memberships ───────────────────────────────────────────────────────
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('team_preference')->nullable();
            $table->boolean('newsletter_opt_in')->default(false);
            $table->string('membership_number')->nullable()->unique();
            $table->timestamps();
        });

        // ── Event Sessions ────────────────────────────────────────────────────
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('session_token', 64)->unique();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('actions_taken')->nullable(); // ["voted","lottery","fotobomb"]
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        // ── Activity Log ──────────────────────────────────────────────────────
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // foto.approved, foto.pushed, lottery.drawn, vote.cast, etc.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // ── Admin Notifications ────────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('event_sessions');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('lottery_entries');
        Schema::dropIfExists('foto_uploads');
        Schema::dropIfExists('events');
    }
};
