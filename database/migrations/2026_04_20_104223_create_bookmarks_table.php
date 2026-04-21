<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->morphs('bookmarkable');

            $table->timestamps();

            $table->unique([
                'user_id',
                'bookmarkable_type',
                'bookmarkable_id',
            ], 'bookmarks_user_bookmarkable_unique');

            $table->index(['bookmarkable_type', 'bookmarkable_id'], 'bookmarks_bookmarkable_index');
            $table->index(['user_id', 'created_at'], 'bookmarks_user_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
