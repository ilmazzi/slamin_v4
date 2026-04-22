<?php

use Domain\PoetrySlam\Enums\PoetrySlamRoundStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poetry_slam_rounds', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('poetry_slam_id')
                ->constrained('poetry_slams')
                ->cascadeOnDelete();

            $table->unsignedInteger('round_number');
            $table->string('name');
            $table->string('status')
                ->default(PoetrySlamRoundStatus::Draft->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['poetry_slam_id', 'round_number'],
                'poetry_slam_rounds_unique_round_number'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poetry_slam_rounds');
    }
};
