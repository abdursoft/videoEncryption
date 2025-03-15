<?php
namespace App\Http\Middleware;

use Closure;

class AuthTokenMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->query('token');

        if (!$token || $token !== session('playback_token')) {
            abort(403, 'Invalid playback token. '.session('playback_token'));
        }

        return $next($request);
    }
}
