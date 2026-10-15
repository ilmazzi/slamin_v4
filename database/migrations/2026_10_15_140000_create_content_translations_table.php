<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table): void {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 10);
            $table->string('title');
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale'], 'content_translations_unique_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
