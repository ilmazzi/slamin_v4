<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Annunci', 'slug' => 'annunci', 'sort_order' => 1],
            ['name' => 'Poesia', 'slug' => 'poesia', 'sort_order' => 2],
            ['name' => 'Tecniche di scrittura', 'slug' => 'tecniche-di-scrittura', 'sort_order' => 3],
            ['name' => 'Eventi e festival', 'slug' => 'eventi-e-festival', 'sort_order' => 4],
            ['name' => 'Interviste', 'slug' => 'interviste', 'sort_order' => 5],
            ['name' => 'Recensioni', 'slug' => 'recensioni', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            DB::table('article_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'slug' => $category['slug'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $categoryId = DB::table('article_categories')
                ->where('slug', $category['slug'])
                ->value('id');

            DB::table('article_category_translations')->updateOrInsert(
                [
                    'article_category_id' => $categoryId,
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
