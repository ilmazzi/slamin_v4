<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_category_id')->constrained('forum_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['forum_category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_category_translations');
    }
};
