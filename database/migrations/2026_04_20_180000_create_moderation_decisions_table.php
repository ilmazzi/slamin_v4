<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('moderatable_type');
            $table->unsignedBigInteger('moderatable_id');
            $table->string('decision', 30);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['moderatable_type', 'moderatable_id']);
            $table->index(['decision', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_decisions');
    }
};
