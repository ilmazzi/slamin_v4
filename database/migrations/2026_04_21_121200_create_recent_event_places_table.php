<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recent_event_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('place_name');
            $table->string('country_code', 2);
            $table->string('city');
            $table->string('postal_code')->nullable();
            $table->string('address_line')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('last_used_at');
            $table->timestamps();

            $table->index(['user_id', 'last_used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recent_event_places');
    }
};
