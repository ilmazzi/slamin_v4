<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_category_id')->constrained('group_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->timestamps();

            $table->unique(['group_category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_category_translations');
    }
};
