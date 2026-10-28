<?php

namespace Database\Seeders;

use Domain\Event\Support\EventCategoryLocaleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['slug' => 'concerto-musica', 'sort_order' => 1],
            ['slug' => 'conferenza-tavola-rotonda', 'sort_order' => 2],
            ['slug' => 'concorso', 'sort_order' => 3],
            ['slug' => 'festival', 'sort_order' => 4],
            ['slug' => 'laboratorio-corso', 'sort_order' => 5],
            ['slug' => 'open-mic', 'sort_order' => 6],
            ['slug' => 'poesia-piu-altra-arte', 'sort_order' => 7],
            ['slug' => 'poetry-slam', 'sort_order' => 8],
            ['slug' => 'presentazione-libro', 'sort_order' => 9],
            ['slug' => 'reading', 'sort_order' => 10],
            ['slug' => 'residenza', 'sort_order' => 11],
            ['slug' => 'spoken-word', 'sort_order' => 12],
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
        }

        EventCategoryLocaleNames::fillTranslations(overwrite: true);
    }
}
