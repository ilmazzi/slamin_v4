<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Media\Actions\RenderMediaShareCardAction;
use Domain\Share\Support\ShareCardFormat;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowMediaShareCardController extends Controller
{
    public function __construct(
        private readonly RenderMediaShareCardAction $renderMediaShareCardAction,
    ) {}

    public function __invoke(Request $request, int $id): Response
    {
        $format = ShareCardFormat::tryFrom((string) $request->query(
            'format',
            $request->route('format', ShareCardFormat::Square->value),
        )) ?? ShareCardFormat::Square;
        $png = $this->renderMediaShareCardAction->execute($id, $format);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
