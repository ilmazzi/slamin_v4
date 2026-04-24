<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poems', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('published_at');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('published_at');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('published_at');
        });

        Schema::table('media_items', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('poems', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::table('media_items', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};

