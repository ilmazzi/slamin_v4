<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('i18n_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('flag', 16)->default('🌐');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('i18n_translation_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('i18n_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_key_id')->constrained('i18n_translation_keys')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('i18n_languages')->cascadeOnDelete();
            $table->text('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['translation_key_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('i18n_translations');
        Schema::dropIfExists('i18n_translation_keys');
        Schema::dropIfExists('i18n_languages');
    }
};
