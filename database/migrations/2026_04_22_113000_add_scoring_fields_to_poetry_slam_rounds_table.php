<?php

use Domain\PoetrySlam\Enums\PoetrySlamRoundScoringType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poetry_slam_rounds', function (Blueprint $table): void {
            $table->unsignedInteger('judges_count')
                ->default(5)
                ->after('name');

            $table->string('scoring_type')
                ->default(PoetrySlamRoundScoringType::TrimmedSum->value)
                ->after('judges_count');
        });
    }

    public function down(): void
    {
        Schema::table('poetry_slam_rounds', function (Blueprint $table): void {
            $table->dropColumn([
                'judges_count',
                'scoring_type',
            ]);
        });
    }
};
