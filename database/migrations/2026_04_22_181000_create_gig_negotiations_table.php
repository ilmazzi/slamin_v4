<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gig_negotiations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete();
            $table->foreignId('gig_application_id')->constrained('gig_applications')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('translator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->decimal('agreed_price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamp('agreed_deadline')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['gig_id', 'gig_application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gig_negotiations');
    }
};
