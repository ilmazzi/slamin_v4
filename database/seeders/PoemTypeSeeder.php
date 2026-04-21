<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PoemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $types = [
            ['name' => 'Verso libero', 'slug' => 'verso-libero', 'sort_order' => 1],
            ['name' => 'Sonetto', 'slug' => 'sonetto', 'sort_order' => 2],
            ['name' => 'Haiku', 'slug' => 'haiku', 'sort_order' => 3],
            ['name' => 'Limerick', 'slug' => 'limerick', 'sort_order' => 4],
            ['name' => 'Ballata', 'slug' => 'ballata', 'sort_order' => 5],
            ['name' => 'Ode', 'slug' => 'ode', 'sort_order' => 6],
            ['name' => 'Elegia', 'slug' => 'elegia', 'sort_order' => 7],
            ['name' => 'Poema epico', 'slug' => 'poema-epico', 'sort_order' => 8],
            ['name' => 'Altro', 'slug' => 'altro', 'sort_order' => 9],
        ];

        foreach ($types as $type) {
            DB::table('poem_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'slug' => $type['slug'],
                    'is_active' => true,
                    'sort_order' => $type['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $typeId = DB::table('poem_types')
                ->where('slug', $type['slug'])
                ->value('id');

            DB::table('poem_type_translations')->updateOrInsert(
                [
                    'poem_type_id' => $typeId,
                    'locale' => 'it',
                ],
                [
                    'name' => $type['name'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
