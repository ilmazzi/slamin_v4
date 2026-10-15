<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Domain\Share\Support\SharePage;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShowMediaSharePageController extends Controller
{
    public function __invoke(Request $request, int $id): Response|RedirectResponse
    {
        $media = MediaItem::query()
            ->whereKey($id)
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->firstOrFail();

        $frontendUrl = SharePublicUrl::for('media', (string) $media->id);

        if (! SharePage::isCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        return response()->view('share.card', [
            'title' => $media->title ?: __('media.ui.untitled'),
            'description' => SharePage::excerpt($media->description),
            'canonicalUrl' => route('share.media.show', ['id' => $media->id]),
            'imageUrl' => $media->type === MediaType::Photo ? $media->resolvedFileUrl() : $media->resolvedThumbnailUrl(),
            'frontendUrl' => $frontendUrl,
        ]);
    }
}
