<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class BlockDownloadExtensions
{
    public function handle($request, Closure $next)
    {
        if ($request->is('hls/*') || $request->is('videos/*')) {
            $ip = $request->ip();
            $cacheKey = "download_attempts:$ip";

            $attempts = cache($cacheKey, 0);
            $attempts++;

            // Allow more requests for streaming users
            cache([$cacheKey => $attempts], now()->addMinutes(2));

            if ($attempts > 200) { // Block only if more than 200 requests in 5 minutes
                Log::warning("Blocked excessive video requests from $ip");
                abort(403, 'Too many requests.');
            }
        }

        return $next($request);
    }
}
