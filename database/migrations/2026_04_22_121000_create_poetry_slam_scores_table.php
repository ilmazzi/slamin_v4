<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poetry_slam_scores', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('poetry_slam_round_id')
                ->constrained('poetry_slam_rounds')
                ->cascadeOnDelete();

            $table->foreignId('poetry_slam_entry_id')
                ->constrained('poetry_slam_entries')
                ->cascadeOnDelete();

            $table->unsignedInteger('judge_number');
            $table->decimal('score', 8, 2);
            $table->timestamps();

            $table->unique(
                ['poetry_slam_round_id', 'poetry_slam_entry_id', 'judge_number'],
                'poetry_slam_scores_unique_round_entry_judge'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poetry_slam_scores');
    }
};
