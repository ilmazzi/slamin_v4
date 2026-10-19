<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_affiliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('child_group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('status', 32);
            $table->foreignId('initiated_by_group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->unique(['parent_group_id', 'child_group_id'], 'group_affiliations_unique');
            $table->index(['child_group_id', 'status']);
            $table->index(['parent_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_affiliations');
    }
};
