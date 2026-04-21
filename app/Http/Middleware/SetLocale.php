<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {

        $locale = null;

        // 1. lingua da header (React)
        if ($request->header('X-Locale')) {
            $locale = $request->header('X-Locale');
        }

        // 2. lingua utente loggato
        elseif ($request->user()?->profile?->preferred_locale) {
            $locale = $request->user()->profile->preferred_locale;
        }

        // 3. lingua browser
        elseif ($request->getPreferredLanguage(['it', 'en'])) {
            $locale = $request->getPreferredLanguage(['it', 'en']);
        }

        // 4. fallback
        App::setLocale($locale ?: 'it');

        return $next($request);
    }
}
