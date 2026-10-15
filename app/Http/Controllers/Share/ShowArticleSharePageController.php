<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Share\Support\SharePage;
use Domain\Share\Support\SharePublicUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ShowArticleSharePageController extends Controller
{
    public function __invoke(Request $request, string $slug): Response|RedirectResponse
    {
        $article = Article::query()
            ->where('slug', $slug)
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->firstOrFail();

        $frontendUrl = SharePublicUrl::for('articles', $article->slug);

        if (! SharePage::isCrawler($request)) {
            return redirect()->away($frontendUrl);
        }

        $excerpt = SharePage::excerpt($article->excerpt ?: $article->content);

        return response()->view('share.card', [
            'title' => $article->title ?: __('articles.all.no_title'),
            'description' => $excerpt !== '' ? $excerpt : __('articles.all.no_excerpt'),
            'canonicalUrl' => route('share.articles.show', ['slug' => $article->slug]),
            'imageUrl' => $article->cover_image_path ? Storage::disk('public')->url($article->cover_image_path) : null,
            'frontendUrl' => $frontendUrl,
        ]);
    }
}
