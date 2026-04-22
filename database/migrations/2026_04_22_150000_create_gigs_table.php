<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('giggable');
            $table->string('type');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('filled_quantity')->default(0);
            $table->string('language')->nullable();
            $table->boolean('hospitality_required')->default(false);
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('filled_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
