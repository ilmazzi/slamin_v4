<?php

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementDecisionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poetry_slam_round_advancement_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poetry_slam_round_id')
                ->constrained('poetry_slam_rounds', indexName: 'ps_round_advancement_decisions_round_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('poetry_slam_entry_id')
                ->constrained('poetry_slam_entries', indexName: 'ps_round_advancement_decisions_entry_id_foreign')
                ->cascadeOnDelete();
            $table->string('decision_status')
                ->default(PoetrySlamRoundAdvancementDecisionStatus::Pending->value);
            $table->text('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['poetry_slam_round_id', 'poetry_slam_entry_id'],
                'poetry_slam_round_advancement_decisions_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poetry_slam_round_advancement_decisions');
    }
};
