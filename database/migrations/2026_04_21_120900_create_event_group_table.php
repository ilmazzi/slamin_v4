<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_group', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedBigInteger('group_id');
            $table->timestamps();

            $table->unique(['event_id', 'group_id']);
            $table->index(['group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_group');
    }
};
