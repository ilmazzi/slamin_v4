<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('gallery_image_paths')->nullable()->after('cover_image_path');
            $table->string('external_link_url')->nullable()->after('promo_video_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['gallery_image_paths', 'external_link_url']);
        });
    }
};
