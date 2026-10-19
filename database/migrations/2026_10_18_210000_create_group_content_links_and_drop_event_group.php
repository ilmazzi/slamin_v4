<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_content_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('content_type', 32);
            $table->unsignedBigInteger('content_id');
            $table->string('status', 32);
            $table->string('source', 32)->default('direct');
            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'content_type', 'content_id'], 'group_content_links_unique');
            $table->index(['group_id', 'status']);
            $table->index(['content_type', 'content_id']);
        });

        Schema::dropIfExists('event_group');
    }

    public function down(): void
    {
        Schema::create('event_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('group_id');
            $table->timestamps();
            $table->unique(['event_id', 'group_id']);
            $table->index(['group_id']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
        });

        Schema::dropIfExists('group_content_links');
    }
};
