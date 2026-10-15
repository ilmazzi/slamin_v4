<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('notification_email_opt_outs')->nullable();
        });

        Schema::table('chat_thread_members', function (Blueprint $table) {
            $table->timestamp('last_emailed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chat_thread_members', function (Blueprint $table) {
            $table->dropColumn('last_emailed_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_email_opt_outs');
        });
    }
};
