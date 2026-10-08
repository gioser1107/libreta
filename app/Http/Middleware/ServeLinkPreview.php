<?php

namespace App\Http\Middleware;

use App\Support\LinkPreview;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServeLinkPreview
{
    /**
     * WhatsApp abandons the card when the first response is a redirect
     * to the login screen, and the chat stays on "Un momento…".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldServe($request)) {
            return $next($request);
        }

        return response()
            ->view('link-preview')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    private function shouldServe(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return false;
        }

        if (str_contains($request->path(), '.')) {
            return false;
        }

        return LinkPreview::isUnfurler($request);
    }
}
