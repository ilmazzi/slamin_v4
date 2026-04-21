<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->uuid('public_id')->unique();

            $table->string('display_name')->nullable();
            $table->string('nickname')->unique();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('website_url')->nullable();
            $table->string('preferred_locale', 10)->default('it');
            $table->boolean('is_public')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('preferred_locale');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
