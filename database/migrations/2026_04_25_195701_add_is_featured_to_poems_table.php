<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poems', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->nullable()->after('published_at');
            $table->integer('featured_rank')->default(0)->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('poems', function (Blueprint $table) {
            $table->dropColumn('is_featured');
            $table->dropColumn('featured_rank');
        });
    }
};
