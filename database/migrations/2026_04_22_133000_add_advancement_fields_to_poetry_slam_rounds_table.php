<?php

use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementBasedOn;
use Domain\PoetrySlam\Enums\PoetrySlamRoundAdvancementMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poetry_slam_rounds', function (Blueprint $table): void {
            $table->string('advancement_mode')
                ->default(PoetrySlamRoundAdvancementMode::AllEntries->value)
                ->after('scoring_type');

            $table->unsignedInteger('advancement_value')
                ->nullable()
                ->after('advancement_mode');

            $table->string('advancement_based_on')
                ->default(PoetrySlamRoundAdvancementBasedOn::RoundResult->value)
                ->after('advancement_value');
        });
    }

    public function down(): void
    {
        Schema::table('poetry_slam_rounds', function (Blueprint $table): void {
            $table->dropColumn([
                'advancement_mode',
                'advancement_value',
                'advancement_based_on',
            ]);
        });
    }
};
