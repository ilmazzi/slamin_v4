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
            if (! Schema::hasColumn('chat_threads', 'audience')) {
                $table->string('audience')->nullable()->after('group_id');
            }

            if (! Schema::hasColumn('chat_threads', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('audience');
            }
        });

        DB::table('chat_threads')
            ->whereNotNull('group_id')
            ->whereNull('audience')
            ->update([
                'audience' => 'public',
                'is_enabled' => true,
            ]);

        if (! Schema::hasIndex('chat_threads', 'chat_threads_group_id_index')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                $table->index('group_id', 'chat_threads_group_id_index');
            });
        }

        if (Schema::hasIndex('chat_threads', 'chat_threads_group_id_unique')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                $table->dropUnique('chat_threads_group_id_unique');
            });
        }

        if (! Schema::hasIndex('chat_threads', 'chat_threads_group_id_audience_unique')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                $table->unique(['group_id', 'audience'], 'chat_threads_group_id_audience_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('chat_threads', 'chat_threads_group_id_audience_unique')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                $table->dropUnique('chat_threads_group_id_audience_unique');
            });
        }

        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'audience')) {
                $table->dropColumn('audience');
            }

            if (Schema::hasColumn('chat_threads', 'is_enabled')) {
                $table->dropColumn('is_enabled');
            }
        });

        if (! Schema::hasIndex('chat_threads', 'chat_threads_group_id_unique')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                $table->unique('group_id');
            });
        }

        if (Schema::hasIndex('chat_threads', 'chat_threads_group_id_index')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                $table->dropIndex('chat_threads_group_id_index');
            });
        }
    }
};
