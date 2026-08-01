<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Decide which language the API answers in.
     *
     * Priority: the signed-in user's saved preference, then the browser's
     * Accept-Language, then the app default. The user's own choice wins because
     * they picked it explicitly; the header is only a guess.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? Locales::fromAcceptLanguage($request->header('Accept-Language'))
            ?? Locales::DEFAULT;

        app()->setLocale(Locales::isSupported($locale) ? $locale : Locales::DEFAULT);

        return $next($request);
    }
}
