<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShowEventSharePageController extends Controller
{
    public function __invoke(Request $request, string $slug): Response|RedirectResponse
    {
        $event = Event::query()
            ->with(['user.profile', 'venueDetail'])
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $frontendUrl = SharePublicUrl::event($event->slug);

        if (! $this->isSocialCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $event->description)) ?? '');
        if (mb_strlen($excerpt) > 180) {
            $excerpt = rtrim(mb_substr($excerpt, 0, 177)).'…';
        }

        return response()->view('share.card', [
            'title' => $event->title ?: __('events.event_title'),
            'description' => $excerpt !== '' ? $excerpt : ($event->subtitle ?: __('events.event_description')),
            'canonicalUrl' => route('share.events.show', ['slug' => $event->slug]),
            'imageUrl' => route('share.events.card', ['slug' => $event->slug]),
            'frontendUrl' => $frontendUrl,
        ]);
    }

    private function isSocialCrawler(Request $request): bool
    {
        $userAgent = (string) $request->userAgent();

        return (bool) preg_match(
            '/facebookexternalhit|Facebot|Twitterbot|WhatsApp|TelegramBot|Slackbot|LinkedInBot|Discordbot|Pinterest|Googlebot|bingbot|Applebot|Iframely|vkShare|redditbot|Embedly|SkypeUriPreview/i',
            $userAgent,
        );
    }
}
