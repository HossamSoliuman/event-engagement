<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('visitor_id', 32);
            $table->enum('page_type', ['landing', 'vidiwall'])->default('landing');
            $table->enum('device_type', ['mobile', 'tablet', 'desktop'])->default('mobile');
            $table->string('os', 40)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('referrer')->nullable();
            $table->boolean('is_first_visit')->default(false);
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
            $table->index(['event_id', 'visitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_page_views');
    }
};
