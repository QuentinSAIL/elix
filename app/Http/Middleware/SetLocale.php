<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = array_keys((array) config('app.supported_locales'));

        $queryLocale = $request->query('lang');
        if ($queryLocale && in_array($queryLocale, $supported, true)) {
            Session::put('locale', $queryLocale);
        }

        $sessionLocale = Session::get('locale');

        if (Auth::check()) {
            /** @var \App\Models\UserPreference|null $userPref */
            $userPref = Auth::user()->preference()->first();
            if ($userPref && in_array($userPref->locale, $supported, true)) {
                $sessionLocale = $userPref->locale;
            }
        }
        $defaultLocale = (string) config('app.locale');
        $localeToUse = in_array($sessionLocale, $supported, true) ? $sessionLocale : $defaultLocale;

        App::setLocale($localeToUse);

        return $next($request);
    }
}
