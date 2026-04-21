<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Concerto (musica)', 'slug' => 'concerto-musica', 'sort_order' => 1],
            ['name' => 'Conferenza/Tavola rotonda', 'slug' => 'conferenza-tavola-rotonda', 'sort_order' => 2],
            ['name' => 'Concorso', 'slug' => 'concorso', 'sort_order' => 3],
            ['name' => 'Festival', 'slug' => 'festival', 'sort_order' => 4],
            ['name' => 'Laboratorio/Corso', 'slug' => 'laboratorio-corso', 'sort_order' => 5],
            ['name' => 'Open mic', 'slug' => 'open-mic', 'sort_order' => 6],
            ['name' => 'Poesia + altra arte', 'slug' => 'poesia-piu-altra-arte', 'sort_order' => 7],
            ['name' => 'Poetry Slam', 'slug' => 'poetry-slam', 'sort_order' => 8],
            ['name' => 'Presentazione libro', 'slug' => 'presentazione-libro', 'sort_order' => 9],
            ['name' => 'Reading', 'slug' => 'reading', 'sort_order' => 10],
            ['name' => 'Residenza', 'slug' => 'residenza', 'sort_order' => 11],
            ['name' => 'Spoken Word', 'slug' => 'spoken-word', 'sort_order' => 12],
        ];

        foreach ($categories as $category) {
            DB::table('event_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'slug' => $category['slug'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $categoryId = DB::table('event_categories')
                ->where('slug', $category['slug'])
                ->value('id');

            DB::table('event_category_translations')->updateOrInsert(
                [
                    'event_category_id' => $categoryId,
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
