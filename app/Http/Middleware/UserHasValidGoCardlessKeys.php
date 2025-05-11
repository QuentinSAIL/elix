<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiService;
use Illuminate\Http\Request;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserHasValidGoCardlessKeys
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->hasApiKey(ApiService::where('name', 'GoCardless')->first()->id)) {
            Toaster::error(__('You need to set up GoCardless API keys before using this feature.'));
            return redirect()->route('settings.api-keys');
        }

        return $next($request);
    }
}
