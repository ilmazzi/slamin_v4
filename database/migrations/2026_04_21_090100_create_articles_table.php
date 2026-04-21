<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('article_category_id')
                ->nullable()
                ->constrained('article_categories')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('language', 10)->default('it');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_public')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('article_category_id');
            $table->index('status');
            $table->index('is_public');
            $table->index('published_at');
            $table->index(['article_category_id', 'status', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
