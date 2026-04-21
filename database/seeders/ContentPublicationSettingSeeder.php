<?php

namespace Database\Seeders;

use Domain\Shared\Enums\ContentType;
use Domain\Shared\Enums\PublicationFlow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentPublicationSettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('content_publication_settings')->updateOrInsert(
            ['content_type' => ContentType::Poem->value],
            [
                'publication_flow' => PublicationFlow::AutoPublish->value,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('content_publication_settings')->updateOrInsert(
            ['content_type' => ContentType::Article->value],
            [
                'publication_flow' => PublicationFlow::AutoPublish->value,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('content_publication_settings')->updateOrInsert(
            ['content_type' => ContentType::Event->value],
            [
                'publication_flow' => PublicationFlow::AutoPublish->value,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
