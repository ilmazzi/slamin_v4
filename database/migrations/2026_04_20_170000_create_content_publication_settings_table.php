<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_publication_settings', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 50)->unique();
            $table->string('publication_flow', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'content_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_publication_settings');
    }
};
