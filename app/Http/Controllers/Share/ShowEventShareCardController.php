<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Event\Actions\RenderEventShareCardAction;
use Symfony\Component\HttpFoundation\Response;

class ShowEventShareCardController extends Controller
{
    public function __construct(
        private readonly RenderEventShareCardAction $renderEventShareCardAction,
    ) {}

    public function __invoke(string $slug): Response
    {
        $png = $this->renderEventShareCardAction->execute($slug);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
