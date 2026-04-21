<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poem_type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poem_type_id')
                ->constrained('poem_types')
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['poem_type_id', 'locale']);
            $table->index(['locale', 'name']);
        });

        $types = DB::table('poem_types')
            ->select('id', 'name')
            ->get();

        $now = now();

        foreach ($types as $type) {
            if (! empty($type->name)) {
                DB::table('poem_type_translations')->updateOrInsert(
                    [
                        'poem_type_id' => $type->id,
                        'locale' => 'it',
                    ],
                    [
                        'name' => $type->name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        Schema::table('poem_types', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('poem_types', function (Blueprint $table) {
            $table->string('name', 100)->default('');
        });

        $translations = DB::table('poem_type_translations')
            ->select('poem_type_id', 'name')
            ->where('locale', 'it')
            ->get();

        foreach ($translations as $translation) {
            DB::table('poem_types')
                ->where('id', $translation->poem_type_id)
                ->update(['name' => $translation->name]);
        }

        Schema::dropIfExists('poem_type_translations');
    }
};
