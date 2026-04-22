<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete();
            $table->foreignId('gig_negotiation_id')->constrained('gig_negotiations')->cascadeOnDelete();
            $table->foreignId('poem_id')->constrained('poems')->cascadeOnDelete();
            $table->foreignId('translator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_language');
            $table->string('target_language');
            $table->decimal('agreed_price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamp('deadline')->nullable();
            $table->longText('translated_content')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique('gig_negotiation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_jobs');
    }
};
