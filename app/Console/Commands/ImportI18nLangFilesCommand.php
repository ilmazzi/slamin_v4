<?php

namespace App\Console\Commands;

use Domain\I18n\Actions\ImportLangFilesAction;
use Illuminate\Console\Command;

class ImportI18nLangFilesCommand extends Command
{
    protected $signature = 'i18n:import {--force : Overwrite existing database translations from lang files}';

    protected $description = 'Seed missing languages, keys and translations from lang/{locale}/*.php into the database';

    public function handle(ImportLangFilesAction $importLangFiles): int
    {
        $result = $importLangFiles->execute((bool) $this->option('force'));

        $this->info(sprintf(
            'Imported %d languages, %d keys, %d translation rows.',
            $result['languages'],
            $result['keys'],
            $result['translations'],
        ));

        return self::SUCCESS;
    }
}
