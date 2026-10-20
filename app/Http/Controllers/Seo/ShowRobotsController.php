<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ShowRobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/').'/sitemap.xml';

        return response(
            "User-agent: *\nDisallow:\n\nSitemap: {$sitemap}\n",
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
