<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Share\Support\SharePage;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShowEventSharePageController extends Controller
{
    public function __invoke(Request $request, string $slug): Response|RedirectResponse
    {
        $event = Event::query()
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $frontendUrl = SharePublicUrl::event($event->slug);

        if (! SharePage::isCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        $excerpt = SharePage::excerpt($event->description);

        return response()->view('share.card', [
            'title' => $event->title ?: __('events.event_title'),
            'description' => $excerpt !== '' ? $excerpt : ($event->subtitle ?: __('events.event_description')),
            'canonicalUrl' => route('share.events.show', ['slug' => $event->slug]),
            'imageUrl' => route('share.events.card', ['slug' => $event->slug]),
            'imageIsCard' => true,
            'frontendUrl' => $frontendUrl,
        ]);
    }
}
