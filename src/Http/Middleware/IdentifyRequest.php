<?php

namespace Jenky\Hades\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenky\Hades\Hades;

class IdentifyRequest
{
    /**
     * Handle an incoming request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        if (Hades::$jsonOutput &&
            Hades::identify($request) &&
            ! $this->wantsJson($request)) {
            // Set default Accept header if not available to force the request
            // to return JSON response
            $request->headers->set('Accept', Hades::$mimeType);
        }

        return $next($request);
    }

    /**
     * Determine if the current request is asking for JSON.
     */
    protected function wantsJson(Request $request): bool
    {
        // We can't use $request->wantsJson() here because it will cache the
        // Accept header for subsequent check.
        return Str::contains($request->header('Accept'), ['/json', '+json']);
    }
}
