<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('group_category_id')
                ->nullable()
                ->after('youtube_url')
                ->constrained('group_categories')
                ->restrictOnDelete();
            $table->boolean('is_listed')->default(true)->after('visibility');
            $table->boolean('auto_accept_events')->default(false)->after('is_listed');
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->string('requested_role', 30)->nullable()->after('role');
        });

        DB::table('groups')->where('visibility', 'public_open')->update([
            'visibility' => 'public',
            'is_listed' => true,
        ]);
        DB::table('groups')->where('visibility', 'private_approval')->update([
            'visibility' => 'private',
        ]);

        DB::table('group_members')->where('role', 'owner')->update(['role' => 'admin']);
        DB::table('group_members')->where('role', 'moderator')->update(['role' => 'editor']);
        DB::table('group_members')->where('role', 'member')->update(['role' => 'follower']);
    }

    public function down(): void
    {
        DB::table('groups')->where('visibility', 'public')->update(['visibility' => 'public_open']);
        DB::table('groups')->where('visibility', 'private')->update(['visibility' => 'private_approval']);

        DB::table('group_members')->where('role', 'admin')->update(['role' => 'owner']);
        DB::table('group_members')->where('role', 'editor')->update(['role' => 'moderator']);
        DB::table('group_members')->where('role', 'follower')->update(['role' => 'member']);

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn('requested_role');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_category_id');
            $table->dropColumn(['is_listed', 'auto_accept_events']);
        });
    }
};
