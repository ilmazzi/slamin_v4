<?php

use Domain\PoetrySlam\Enums\PoetrySlamEntrySource;
use Domain\PoetrySlam\Enums\PoetrySlamEntryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poetry_slam_entries', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('poetry_slam_id')
                ->constrained('poetry_slams')
                ->cascadeOnDelete();

            $table->string('entry_source')
                ->default(PoetrySlamEntrySource::EventParticipant->value);

            $table->foreignId('event_participant_invite_id')
                ->nullable()
                ->constrained('event_participant_invites')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('display_name');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')
                ->default(PoetrySlamEntryStatus::Registered->value);

            $table->unsignedInteger('performance_order')->nullable();

            $table->timestamps();

            $table->unique(
                ['poetry_slam_id', 'event_participant_invite_id'],
                'poetry_slam_entries_unique_event_invite'
            );

            $table->unique(
                ['poetry_slam_id', 'user_id'],
                'poetry_slam_entries_unique_user'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poetry_slam_entries');
    }
};