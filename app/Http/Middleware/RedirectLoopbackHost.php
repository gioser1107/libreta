<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLoopbackHost
{
    /**
     * Face ID and passkeys only work on a hostname, never on an IP address.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() !== '127.0.0.1') {
            return $next($request);
        }

        $port = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return redirect()->away(
            $request->getScheme().'://localhost'.$portSuffix.$request->getRequestUri()
        );
    }
}
