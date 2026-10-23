<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            I18nSeeder::class,
            ContentPublicationSettingSeeder::class,
            PoemCategorySeeder::class,
            PoemTypeSeeder::class,
            PoemSeeder::class,
            ArticleCategorySeeder::class,
            ArticleSeeder::class,
            EventCategorySeeder::class,
            EventSeeder::class,
            GroupCategorySeeder::class,
            ForumCategorySeeder::class,
            HelpSeeder::class,
        ]);
    }
}
