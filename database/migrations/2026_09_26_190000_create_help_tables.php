<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('help_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_category_id')
                ->constrained('help_categories')
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['help_category_id', 'locale']);
            $table->index(['locale', 'name']);
        });

        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_category_id')
                ->constrained('help_categories')
                ->restrictOnDelete();
            $table->string('slug', 160)->unique();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('help_article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_article_id')
                ->constrained('help_articles')
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 180);
            $table->text('body');
            $table->timestamps();

            $table->unique(['help_article_id', 'locale']);
            $table->index(['locale', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_article_translations');
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_category_translations');
        Schema::dropIfExists('help_categories');
    }
};
