<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('visibility')->default('private');
            $table->string('status')->default('processing');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_url')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('peertube_video_id')->nullable();
            $table->string('peertube_uuid')->nullable();
            $table->string('peertube_short_uuid')->nullable();
            $table->string('peertube_url')->nullable();
            $table->string('peertube_embed_url')->nullable();
            $table->string('peertube_thumbnail_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('processing_status')->nullable();
            $table->text('upload_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'visibility']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
