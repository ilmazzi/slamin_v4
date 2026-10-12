<?php

namespace Database\Seeders;

use Domain\I18n\Actions\ImportLangFilesAction;
use Illuminate\Database\Seeder;

class I18nSeeder extends Seeder
{
    public function run(ImportLangFilesAction $importLangFiles): void
    {
        $importLangFiles->execute();
    }
}
