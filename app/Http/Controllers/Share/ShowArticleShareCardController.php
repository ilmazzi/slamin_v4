<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Article\Actions\RenderArticleShareCardAction;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardTemplate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowArticleShareCardController extends Controller
{
    public function __construct(
        private readonly RenderArticleShareCardAction $renderArticleShareCardAction,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $format = ShareCardFormat::tryFrom((string) $request->query(
            'format',
            $request->route('format', ShareCardFormat::Square->value),
        )) ?? ShareCardFormat::Square;
        $template = ShareCardTemplate::fromRequest(
            'article',
            $request->query('template', ShareCardTemplate::defaultFor('article')->value),
            $request->route()?->getName(),
        );
        $png = $this->renderArticleShareCardAction->execute($slug, $format, $template);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
