<?php

namespace Domain\Seo\Actions;

use Illuminate\Support\Facades\File;

class WriteSitemapFilesAction
{
    public function __construct(
        private readonly RenderSitemapXmlAction $renderSitemap,
    ) {}

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    public function execute(array $paths = []): array
    {
        $xml = $this->renderSitemap->execute();
        $written = [];

        foreach ($this->resolvePaths($paths) as $path) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $xml);
            $written[] = $path;
        }

        return $written;
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function resolvePaths(array $paths): array
    {
        if ($paths !== []) {
            return array_values(array_unique($paths));
        }

        return array_values(array_unique(array_filter([
            public_path('sitemap.xml'),
            base_path('frontend/public/sitemap.xml'),
            base_path('frontend/dist/sitemap.xml'),
        ], fn (string $path): bool => File::isDirectory(dirname($path)))));
    }
}
