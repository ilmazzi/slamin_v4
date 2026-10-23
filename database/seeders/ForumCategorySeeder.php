<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            [
                'slug' => 'poesia',
                'sort_order' => 1,
                'translations' => [
                    'it' => ['name' => 'Poesia', 'description' => 'Versi, slam, letture e confronti sulla scrittura.'],
                    'en' => ['name' => 'Poetry', 'description' => 'Verses, slam, readings and conversations about writing.'],
                ],
            ],
            [
                'slug' => 'eventi',
                'sort_order' => 2,
                'translations' => [
                    'it' => ['name' => 'Eventi', 'description' => 'Festival, open mic, date e vita della scena.'],
                    'en' => ['name' => 'Events', 'description' => 'Festivals, open mics, dates and scene life.'],
                ],
            ],
            [
                'slug' => 'feedback',
                'sort_order' => 3,
                'translations' => [
                    'it' => ['name' => 'Feedback', 'description' => 'Idee, bug e proposte per migliorare Slam In.'],
                    'en' => ['name' => 'Feedback', 'description' => 'Ideas, bugs and proposals to improve Slam In.'],
                ],
            ],
            [
                'slug' => 'off-topic',
                'sort_order' => 4,
                'translations' => [
                    'it' => ['name' => 'Off topic', 'description' => 'Tutto il resto, purché resti rispetto.'],
                    'en' => ['name' => 'Off topic', 'description' => 'Everything else, as long as it stays respectful.'],
                ],
            ],
        ];

        foreach ($categories as $category) {
            DB::table('forum_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'slug' => $category['slug'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $categoryId = DB::table('forum_categories')
                ->where('slug', $category['slug'])
                ->value('id');

            foreach ($category['translations'] as $locale => $translation) {
                DB::table('forum_category_translations')->updateOrInsert(
                    [
                        'forum_category_id' => $categoryId,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
