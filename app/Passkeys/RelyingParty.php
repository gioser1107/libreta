<?php

namespace App\Passkeys;

class RelyingParty
{
    /**
     * WebAuthn rejects IP addresses. Loopback must be the name localhost.
     */
    public static function id(?string $host): string
    {
        if ($host === null || $host === '' || self::isLoopback($host)) {
            return 'localhost';
        }

        if (str_contains($host, '://')) {
            $parsed = parse_url($host, PHP_URL_HOST);

            return self::id(is_string($parsed) ? $parsed : null);
        }

        return $host;
    }

    public static function idFromAppUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            $host = parse_url('https://'.$url, PHP_URL_HOST);
        }

        return self::id(is_string($host) ? $host : null);
    }

    public static function accepts(string $host): bool
    {
        if ($host === 'localhost' || self::isLoopback($host)) {
            return true;
        }

        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $host) === 1;
    }

    /**
     * @return list<string>
     */
    public static function originsForHost(string $host, string $schemeAndHost): array
    {
        $origins = [rtrim($schemeAndHost, '/')];

        if (self::id($host) !== 'localhost') {
            $origins[] = 'https://'.self::id($host);
        }

        return array_values(array_unique($origins));
    }

    /**
     * @param  list<string>  $origins
     * @return list<string>
     */
    public static function origins(array $origins): array
    {
        $normalized = [];

        foreach ($origins as $origin) {
            $normalized[] = $origin;

            $local = str_replace(
                ['://127.0.0.1', '://[::1]'],
                '://localhost',
                $origin,
            );

            if ($local !== $origin) {
                $normalized[] = $local;
            }
        }

        return array_values(array_unique($normalized));
    }

    private static function isLoopback(string $host): bool
    {
        return in_array($host, ['127.0.0.1', '::1', '[::1]'], true);
    }
}
