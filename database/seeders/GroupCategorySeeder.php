<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            [
                'slug' => 'progetto-artistico',
                'sort_order' => 1,
                'translations' => [
                    'it' => 'Progetto artistico',
                    'en' => 'Artistic project',
                ],
            ],
            [
                'slug' => 'associazione-organizzazione',
                'sort_order' => 2,
                'translations' => [
                    'it' => 'Associazione / organizzazione',
                    'en' => 'Association / organization',
                ],
            ],
            [
                'slug' => 'luogo',
                'sort_order' => 3,
                'translations' => [
                    'it' => 'Luogo',
                    'en' => 'Place',
                ],
            ],
            [
                'slug' => 'altro',
                'sort_order' => 4,
                'translations' => [
                    'it' => 'Altro',
                    'en' => 'Other',
                ],
            ],
        ];

        foreach ($categories as $category) {
            DB::table('group_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'slug' => $category['slug'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $categoryId = DB::table('group_categories')
                ->where('slug', $category['slug'])
                ->value('id');

            foreach ($category['translations'] as $locale => $name) {
                DB::table('group_category_translations')->updateOrInsert(
                    [
                        'group_category_id' => $categoryId,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $name,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
