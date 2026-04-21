<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ContentPublicationSettingSeeder::class,
            PoemCategorySeeder::class,
            PoemTypeSeeder::class,
            ArticleCategorySeeder::class,
            EventCategorySeeder::class,
        ]);
    }
}
