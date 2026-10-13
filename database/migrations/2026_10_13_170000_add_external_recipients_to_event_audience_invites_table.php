<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_audience_invites', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('name');
            $table->string('invite_type', 20)->default('internal')->after('email');
            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('event_audience_invites', function (Blueprint $table): void {
            $table->dropUnique(['event_id', 'email']);
            $table->dropColumn(['name', 'email', 'invite_type']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
