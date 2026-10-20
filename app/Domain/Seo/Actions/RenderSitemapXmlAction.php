<?php

namespace Domain\Seo\Actions;

class RenderSitemapXmlAction
{
    public function __construct(
        private readonly BuildSitemapAction $buildSitemap,
    ) {}

    public function execute(): string
    {
        return view('seo.sitemap', [
            'urls' => $this->buildSitemap->execute(),
        ])->render();
    }
}
