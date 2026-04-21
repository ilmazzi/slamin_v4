<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PoemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Amore', 'slug' => 'amore', 'sort_order' => 1],
            ['name' => 'Natura', 'slug' => 'natura', 'sort_order' => 2],
            ['name' => 'Sociale', 'slug' => 'sociale', 'sort_order' => 3],
            ['name' => 'Politica', 'slug' => 'politica', 'sort_order' => 4],
            ['name' => 'Personale', 'slug' => 'personale', 'sort_order' => 5],
            ['name' => 'Filosofia', 'slug' => 'filosofia', 'sort_order' => 6],
            ['name' => 'Religione', 'slug' => 'religione', 'sort_order' => 7],
            ['name' => 'Guerra', 'slug' => 'guerra', 'sort_order' => 8],
            ['name' => 'Pace', 'slug' => 'pace', 'sort_order' => 9],
            ['name' => 'Morte', 'slug' => 'morte', 'sort_order' => 10],
            ['name' => 'Vita', 'slug' => 'vita', 'sort_order' => 11],
            ['name' => 'Amicizia', 'slug' => 'amicizia', 'sort_order' => 12],
            ['name' => 'Famiglia', 'slug' => 'famiglia', 'sort_order' => 13],
            ['name' => 'Lavoro', 'slug' => 'lavoro', 'sort_order' => 14],
            ['name' => 'Viaggio', 'slug' => 'viaggio', 'sort_order' => 15],
            ['name' => 'Altro', 'slug' => 'altro', 'sort_order' => 16],
        ];

        foreach ($categories as $category) {
            DB::table('poem_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'slug' => $category['slug'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $categoryId = DB::table('poem_categories')
                ->where('slug', $category['slug'])
                ->value('id');

            DB::table('poem_category_translations')->updateOrInsert(
                [
                    'poem_category_id' => $categoryId,
                    'locale' => 'it',
                ],
                [
                    'name' => $category['name'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
