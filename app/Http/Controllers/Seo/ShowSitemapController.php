<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use Domain\Seo\Actions\BuildSitemapAction;
use Illuminate\Http\Response;

class ShowSitemapController extends Controller
{
    public function __invoke(BuildSitemapAction $action): Response
    {
        return response()
            ->view('seo.sitemap', [
                'urls' => $action->execute(),
            ], 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
            ]);
    }
}
