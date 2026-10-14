<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShowPoemSharePageController extends Controller
{
    public function __invoke(Request $request, string $slug): Response|RedirectResponse
    {
        $poem = Poem::query()
            ->with('user.profile')
            ->where('slug', $slug)
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/').'/poems/'.$poem->slug;

        if (! $this->isSocialCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $poem->content)) ?? '');
        if (mb_strlen($excerpt) > 180) {
            $excerpt = rtrim(mb_substr($excerpt, 0, 177)).'…';
        }

        return response()->view('share.card', [
            'title' => $poem->title ?: __('poems.all.no_title'),
            'description' => $excerpt !== '' ? $excerpt : __('poems.all.no_excerpt'),
            'canonicalUrl' => route('share.poems.show', ['slug' => $poem->slug]),
            'imageUrl' => route('share.poems.card', ['slug' => $poem->slug]),
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
