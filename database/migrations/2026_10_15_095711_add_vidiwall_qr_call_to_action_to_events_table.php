<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('vidiwall_qr_headline', 80)->nullable()->after('vidiwall_qr_background_path');
            $table->string('vidiwall_qr_small_text', 140)->nullable()->after('vidiwall_qr_headline');
            $table->string('vidiwall_qr_text_color', 7)->nullable()->after('vidiwall_qr_small_text');
            $table->string('vidiwall_qr_panel', 10)->default('none')->after('vidiwall_qr_text_color');
            $table->string('vidiwall_qr_panel_color', 7)->nullable()->after('vidiwall_qr_panel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'vidiwall_qr_headline',
                'vidiwall_qr_small_text',
                'vidiwall_qr_text_color',
                'vidiwall_qr_panel',
                'vidiwall_qr_panel_color',
            ]);
        });
    }
};
