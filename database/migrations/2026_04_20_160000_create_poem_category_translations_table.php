<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poem_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poem_category_id')
                ->constrained('poem_categories')
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['poem_category_id', 'locale']);
            $table->index(['locale', 'name']);
        });

        $categories = DB::table('poem_categories')
            ->select('id', 'name')
            ->get();

        $now = now();

        foreach ($categories as $category) {
            if (! empty($category->name)) {
                DB::table('poem_category_translations')->updateOrInsert(
                    [
                        'poem_category_id' => $category->id,
                        'locale' => 'it',
                    ],
                    [
                        'name' => $category->name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        Schema::table('poem_categories', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('poem_categories', function (Blueprint $table) {
            $table->string('name', 100)->default('');
        });

        $translations = DB::table('poem_category_translations')
            ->select('poem_category_id', 'name')
            ->where('locale', 'it')
            ->get();

        foreach ($translations as $translation) {
            DB::table('poem_categories')
                ->where('id', $translation->poem_category_id)
                ->update(['name' => $translation->name]);
        }

        Schema::dropIfExists('poem_category_translations');
    }
};
