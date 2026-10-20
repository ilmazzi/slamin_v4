<?php

namespace App\Console\Commands;

use Domain\Seo\Actions\WriteSitemapFilesAction;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate {--path= : Write the sitemap only to this file}';

    protected $description = 'Write the public sitemap XML where nginx can serve it as a static file';

    public function handle(WriteSitemapFilesAction $action): int
    {
        $path = trim((string) $this->option('path'));
        $written = $action->execute($path !== '' ? [$path] : []);

        foreach ($written as $file) {
            $this->info($file);
        }

        return self::SUCCESS;
    }
}
