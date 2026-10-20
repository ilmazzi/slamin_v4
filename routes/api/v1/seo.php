<?php

use App\Http\Controllers\Seo\ShowSitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', ShowSitemapController::class)->name('api.v1.sitemap');
