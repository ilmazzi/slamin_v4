<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_category_id')
                ->constrained('article_categories')
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['article_category_id', 'locale']);
            $table->index(['locale', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category_translations');
    }
};
