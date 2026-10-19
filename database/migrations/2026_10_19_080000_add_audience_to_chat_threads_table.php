<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->string('audience')->nullable()->after('group_id');
            $table->boolean('is_enabled')->default(true)->after('audience');
        });

        DB::table('chat_threads')
            ->whereNotNull('group_id')
            ->whereNull('audience')
            ->update([
                'audience' => 'public',
                'is_enabled' => true,
            ]);

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropUnique(['group_id']);
            $table->unique(['group_id', 'audience']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'audience']);
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn(['audience', 'is_enabled']);
            $table->unique('group_id');
        });
    }
};
