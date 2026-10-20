<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use Domain\Seo\Actions\RenderSitemapXmlAction;
use Illuminate\Http\Response;

class ShowSitemapController extends Controller
{
    public function __invoke(RenderSitemapXmlAction $action): Response
    {
        return response($action->execute(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
