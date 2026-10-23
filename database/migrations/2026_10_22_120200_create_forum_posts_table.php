<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forum_category_id')->constrained('forum_categories')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('body');
            $table->string('status', 20)->default('published');
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->integer('score')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['forum_category_id', 'slug']);
            $table->index(['status', 'created_at']);
            $table->index(['status', 'score']);
            $table->index(['forum_category_id', 'created_at']);
            $table->index(['forum_category_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
