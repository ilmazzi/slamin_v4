<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Gig\Actions\RenderGigShareCardAction;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardTemplate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowGigShareCardController extends Controller
{
    public function __construct(
        private readonly RenderGigShareCardAction $renderGigShareCardAction,
    ) {}

    public function __invoke(Request $request, int $id): Response
    {
        $format = ShareCardFormat::tryFrom((string) $request->query(
            'format',
            $request->route('format', ShareCardFormat::Square->value),
        )) ?? ShareCardFormat::Square;
        $template = ShareCardTemplate::fromRequest(
            'gig',
            $request->query('template', ShareCardTemplate::defaultFor('gig')->value),
            $request->route()?->getName(),
        );
        $png = $this->renderGigShareCardAction->execute($id, $format, $template);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
