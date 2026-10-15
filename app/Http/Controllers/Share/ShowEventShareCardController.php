<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Event\Actions\RenderEventShareCardAction;
use Domain\Share\Support\ShareCardFormat;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowEventShareCardController extends Controller
{
    public function __construct(
        private readonly RenderEventShareCardAction $renderEventShareCardAction,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $format = ShareCardFormat::from((string) $request->route('format', ShareCardFormat::Square->value));
        $png = $this->renderEventShareCardAction->execute($slug, $format);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
