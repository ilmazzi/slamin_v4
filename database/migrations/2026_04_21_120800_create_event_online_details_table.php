<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_online_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->string('meeting_url');
            $table->string('timezone', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_online_details');
    }
};
