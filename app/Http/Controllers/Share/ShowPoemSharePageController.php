<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\SharePage;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShowPoemSharePageController extends Controller
{
    public function __invoke(Request $request, string $slug): Response|RedirectResponse
    {
        $poem = Poem::query()
            ->where('slug', $slug)
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $frontendUrl = SharePublicUrl::poem($poem->slug);

        if (! SharePage::isCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        $excerpt = SharePage::excerpt($poem->content);

        return response()->view('share.card', [
            'title' => $poem->title ?: __('poems.all.no_title'),
            'description' => $excerpt !== '' ? $excerpt : __('poems.all.no_excerpt'),
            'canonicalUrl' => route('share.poems.show', ['slug' => $poem->slug]),
            'imageUrl' => route('share.poems.preview', ['slug' => $poem->slug]),
            'imageSize' => ShareCardFormat::Landscape,
            'frontendUrl' => $frontendUrl,
        ]);
    }
}
