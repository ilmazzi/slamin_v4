<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // ID pubblico (per API, URL, sicurezza)
            $table->uuid('public_id')->unique();

            // Auth base
            $table->string('email')->unique();
            $table->string('password');

            // Stato utente
            $table->string('status')->default('active'); // active, suspended, banned

            // Verifica email
            $table->timestamp('email_verified_at')->nullable();

            // Remember token
            $table->rememberToken();

            // Soft delete
            $table->softDeletes();

            // Timestamps
            $table->timestamps();

            // Index utili
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
