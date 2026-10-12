<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Supported locales whitelist.
     */
    public const SUPPORTED_LOCALES = ['id', 'en'];

    /**
     * Default fallback locale.
     */
    public const DEFAULT_LOCALE = 'id';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // 1. Session preference
        if ($request->hasSession() && $request->session()->has('locale')) {
            $sessionLocale = $request->session()->get('locale');
            if (in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
                $locale = $sessionLocale;
            }
        }

        // 2. Fallback to default
        if (! $locale) {
            $locale = self::DEFAULT_LOCALE;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
