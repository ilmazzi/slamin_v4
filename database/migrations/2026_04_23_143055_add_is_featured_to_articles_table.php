<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
           $table->boolean('is_featured')->default(false)->after('published_at')->nullable();
           $table->integer('featured_rank')->default(0)->after('is_featured')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_featured');
            $table->dropColumn('featured_rank');
        });
    }
};
