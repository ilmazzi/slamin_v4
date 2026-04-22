<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_availability_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_availability_option_id')->constrained('event_availability_options')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_availability_option_id', 'user_id'], 'event_availability_votes_option_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_availability_votes');
    }
};
