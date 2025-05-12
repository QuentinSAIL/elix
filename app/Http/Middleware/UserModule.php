<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserModule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->modules()->count() === 0) {
            return redirect()->route('settings')->with('error', 'You do not have access to any modules.');
        } else {
            $path = trim($request->path(), '/');
            $endpoint = explode('/', $path)[0] ?? '';
            $userModules = $request->user()->modules->pluck('endpoint')->toArray();
            if (!in_array($endpoint, $userModules)) {
                return redirect()->route('settings')->with('error', 'You do not have access to this module.');
            }
            return $next($request);
        }
    }
}
