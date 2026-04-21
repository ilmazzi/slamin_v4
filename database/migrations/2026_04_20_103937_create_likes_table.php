<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->morphs('likeable');

            $table->timestamps();

            $table->unique([
                'user_id',
                'likeable_type',
                'likeable_id',
            ], 'likes_user_likeable_unique');

            $table->index(['likeable_type', 'likeable_id'], 'likes_likeable_index');
            $table->index(['user_id', 'created_at'], 'likes_user_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
