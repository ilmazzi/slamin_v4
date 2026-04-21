<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poems', function (Blueprint $table) {
            $table->foreignId('poem_category_id')
                ->nullable()
                ->after('user_id')
                ->constrained('poem_categories')
                ->nullOnDelete();

            $table->foreignId('poem_type_id')
                ->nullable()
                ->after('poem_category_id')
                ->constrained('poem_types')
                ->nullOnDelete();

            $table->index(['poem_category_id', 'status', 'is_public']);
            $table->index(['poem_type_id', 'status', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::table('poems', function (Blueprint $table) {
            $table->dropIndex(['poem_category_id', 'status', 'is_public']);
            $table->dropIndex(['poem_type_id', 'status', 'is_public']);

            $table->dropConstrainedForeignId('poem_type_id');
            $table->dropConstrainedForeignId('poem_category_id');
        });
    }
};
