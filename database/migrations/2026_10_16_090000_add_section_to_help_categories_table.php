<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_categories', function (Blueprint $table) {
            $table->string('section', 20)->default('help')->after('slug');
            $table->index(['section', 'is_active', 'sort_order']);
        });

        DB::table('help_categories')
            ->whereRaw('LOWER(slug) = ?', ['faq'])
            ->update(['section' => 'faq']);
    }

    public function down(): void
    {
        Schema::table('help_categories', function (Blueprint $table) {
            $table->dropIndex(['section', 'is_active', 'sort_order']);
            $table->dropColumn('section');
        });
    }
};
