<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->timestamps();

            $table->unique(['event_category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_category_translations');
    }
};
