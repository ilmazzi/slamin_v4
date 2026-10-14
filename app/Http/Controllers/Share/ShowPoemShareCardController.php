<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Poetry\Actions\RenderPoemShareCardAction;
use Symfony\Component\HttpFoundation\Response;

class ShowPoemShareCardController extends Controller
{
    public function __construct(
        private readonly RenderPoemShareCardAction $renderPoemShareCardAction,
    ) {}

    public function __invoke(string $slug): Response
    {
        $png = $this->renderPoemShareCardAction->execute($slug);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
