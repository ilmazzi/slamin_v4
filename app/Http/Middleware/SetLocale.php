<?php

namespace App\Http\Middleware;

use Closure;
use Domain\I18n\Actions\ResolveDefaultLocaleAction;
use Domain\I18n\Actions\ResolveSupportedLocalesAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = app(ResolveSupportedLocalesAction::class)->execute();
        $default = app(ResolveDefaultLocaleAction::class)->execute();
        $locale = null;

        $headerLocale = $this->normalize($request->header('X-Locale'));
        if ($headerLocale && in_array($headerLocale, $supported, true)) {
            $locale = $headerLocale;
        } elseif ($request->user()?->profile?->preferred_locale) {
            $preferred = $this->normalize($request->user()->profile->preferred_locale);
            if ($preferred && in_array($preferred, $supported, true)) {
                $locale = $preferred;
            }
        } elseif ($request->getPreferredLanguage($supported)) {
            $locale = $request->getPreferredLanguage($supported);
        }

        App::setLocale($locale ?: $default);

        return $next($request);
    }

    private function normalize(mixed $locale): ?string
    {
        if (! is_string($locale) || $locale === '') {
            return null;
        }

        return strtolower(substr($locale, 0, 10));
    }
}
