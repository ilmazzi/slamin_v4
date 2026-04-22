<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_checkout_orders', function (Blueprint $table) {
            $table->string('provider_checkout_session_id')->nullable()->after('provider');
            $table->string('provider_payment_intent_id')->nullable()->after('provider_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('media_checkout_orders', function (Blueprint $table) {
            $table->dropColumn([
                'provider_checkout_session_id',
                'provider_payment_intent_id',
            ]);
        });
    }
};
