<?php

use Domain\PoetrySlam\Enums\PoetrySlamStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poetry_slams', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete()
                ->unique();

            $table->string('status')
                ->default(PoetrySlamStatus::Draft->value);

            $table->text('rules_summary')->nullable();
            $table->unsignedInteger('slots_limit')->nullable();
            $table->string('judging_mode')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poetry_slams');
    }
};