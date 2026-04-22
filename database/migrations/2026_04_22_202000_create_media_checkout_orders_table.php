<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_checkout_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_feature_pack_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('provider')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('feature_code')->nullable();
            $table->string('feature_name')->nullable();
            $table->json('feature_config')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_checkout_orders');
    }
};
