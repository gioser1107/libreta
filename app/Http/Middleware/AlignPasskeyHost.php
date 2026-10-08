<?php

namespace App\Http\Middleware;

use App\Passkeys\RelyingParty;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlignPasskeyHost
{
    /**
     * Bind Face ID to the domain the browser actually opened.
     *
     * A missing or bare APP_URL leaves the relying party empty and the
     * passkey endpoints crash before the phone can ask for Face ID.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (! RelyingParty::accepts($host)) {
            $this->ensureRelyingPartyIsString();

            return $next($request);
        }

        config(['passkeys.relying_party_id' => RelyingParty::id($host)]);

        $origins = config('passkeys.allowed_origins');
        $origins = is_array($origins) ? $origins : [];

        foreach (RelyingParty::originsForHost($host, $request->getSchemeAndHttpHost()) as $origin) {
            if (! in_array($origin, $origins, true)) {
                $origins[] = $origin;
            }
        }

        config([
            'passkeys.allowed_origins' => array_values(array_filter(
                $origins,
                is_string(...),
            )),
        ]);

        return $next($request);
    }

    private function ensureRelyingPartyIsString(): void
    {
        $relyingPartyId = config('passkeys.relying_party_id');

        if (! is_string($relyingPartyId) || $relyingPartyId === '') {
            config(['passkeys.relying_party_id' => 'localhost']);
        }
    }
}
