<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique la langue de l'utilisateur (fr ou en) pour chaque requête web.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['fr', 'en'];

        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $request->getPreferredLanguage($supported)
            ?? 'fr';

        if (! in_array($locale, $supported, true)) {
            $locale = 'fr';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
