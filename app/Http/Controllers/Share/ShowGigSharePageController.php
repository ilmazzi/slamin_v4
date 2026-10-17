<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\SharePage;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShowGigSharePageController extends Controller
{
    public function __invoke(Request $request, int $id): Response|RedirectResponse
    {
        $gig = Gig::query()
            ->with('giggable')
            ->whereKey($id)
            ->where('status', GigStatus::Open)
            ->withPublicParent()
            ->firstOrFail();

        $frontendUrl = SharePublicUrl::for('gigs', (string) $gig->id);

        if (! SharePage::isCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        $title = trim((string) ($gig->giggable?->title ?? '')) ?: __('gigs.card.untitled');

        return response()->view('share.card', [
            'title' => $title,
            'description' => SharePage::excerpt($gig->description),
            'canonicalUrl' => route('share.gigs.show', ['id' => $gig->id]),
            'imageUrl' => route('share.gigs.preview', ['id' => $gig->id]),
            'imageSize' => ShareCardFormat::Landscape,
            'frontendUrl' => $frontendUrl,
        ]);
    }
}
