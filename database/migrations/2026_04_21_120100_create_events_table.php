<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained('event_categories')->nullOnDelete();
            $table->foreignId('festival_event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug')->unique();
            $table->longText('description');
            $table->text('requirements')->nullable();
            $table->string('schedule_mode', 30);
            $table->string('delivery_mode', 30);
            $table->string('cover_image_path')->nullable();
            $table->boolean('has_cost')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('promo_video_url')->nullable();
            $table->timestamp('registration_deadline')->nullable();
            $table->boolean('public_invite_enabled')->default(false);
            $table->boolean('audience_capacity_enabled')->default(false);
            $table->unsignedInteger('audience_capacity')->nullable();
            $table->string('language', 10)->default('it');
            $table->string('status', 30)->default('draft');
            $table->boolean('is_public')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_category_id', 'status', 'is_public']);
            $table->index(['festival_event_id']);
            $table->index(['schedule_mode', 'delivery_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
