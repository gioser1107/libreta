<?php

namespace App\Services;

use App\Models\BcvRate;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculatorQuoteService
{
    private const BINANCE_URL = 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search';

    private const CACHE_KEY = 'calculator.binance-p2p-ves';

    /**
     * Tasas para la calculadora: BCV vigente (la misma del cuaderno) y USDT de Binance P2P.
     *
     * @return array{
     *     rates: array{USD: ?float, EUR: ?float, USDT_BUY: ?float, USDT_SELL: ?float},
     *     labels: array{USD: ?string, EUR: ?string, USDT_BUY: string, USDT_SELL: string},
     *     available: bool,
     *     error: ?string
     * }
     */
    public function quote(bool $fresh = false): array
    {
        $today = now();
        $onDate = $today->toDateString();
        $usd = BcvRate::forCodeOnOrBefore('USD', $onDate);
        $eur = BcvRate::forCodeOnOrBefore('EUR', $onDate);
        $p2p = $this->binanceRates($fresh);
        $todayLabel = $this->headlineDate($today);

        $usdRate = $this->positiveRate($usd?->rate_to_ves);
        $eurRate = $this->positiveRate($eur?->rate_to_ves);

        return [
            'rates' => [
                'USD' => $usdRate,
                'EUR' => $eurRate,
                'USDT_BUY' => $p2p['buy'],
                'USDT_SELL' => $p2p['sell'],
            ],
            'labels' => [
                'USD' => $usd ? $this->headlineDate($usd->date_effective) : null,
                'EUR' => $eur ? $this->headlineDate($eur->date_effective) : null,
                'USDT_BUY' => $todayLabel,
                'USDT_SELL' => $todayLabel,
            ],
            'available' => $usdRate !== null,
            'error' => $usdRate === null ? 'Tasa no disponible actualmente' : null,
        ];
    }

    public function headlineDate(Carbon $date): string
    {
        $label = $date->copy()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

        return collect(explode(' ', $label))
            ->map(fn (string $word): string => $word === 'de' ? $word : mb_convert_case($word, MB_CASE_TITLE, 'UTF-8'))
            ->implode(' ');
    }

    /**
     * @param  array{USD: ?float, EUR: ?float, USDT_BUY: ?float, USDT_SELL: ?float}  $rates
     * @return array{buy: ?array{text: string, tone: string}, sell: ?array{text: string, tone: string}}
     */
    public function gaps(array $rates): array
    {
        return [
            'buy' => $this->gap($rates['USDT_BUY'] ?? null, $rates['USD'] ?? null),
            'sell' => $this->gap($rates['USDT_SELL'] ?? null, $rates['USD'] ?? null),
        ];
    }

    public function formatHero(?float $rate): string
    {
        if ($rate === null) {
            return '';
        }

        return number_format($rate, 2, ',', '.');
    }

    /**
     * @return array{buy: ?float, sell: ?float}
     */
    private function binanceRates(bool $fresh): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        /** @var array{buy: ?float, sell: ?float} $rates */
        $rates = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            return [
                'buy' => $this->averageOffers('BUY'),
                'sell' => $this->averageOffers('SELL'),
            ];
        });

        if ($rates['buy'] === null && $rates['sell'] === null) {
            Cache::forget(self::CACHE_KEY);
        }

        return $rates;
    }

    private function averageOffers(string $tradeType): ?float
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->retry(2, 150, function (Throwable $exception): bool {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->serverError() || $exception->response->status() === 429));
                }, throw: false)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                ])
                ->post(self::BINANCE_URL, [
                    'asset' => 'USDT',
                    'fiat' => 'VES',
                    'tradeType' => $tradeType,
                    'page' => 1,
                    'rows' => 10,
                    'payTypes' => [],
                    'publisherType' => null,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Binance P2P no respondió', [
                'trade' => $tradeType,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->failed() || $response->json('code') !== '000000') {
            return null;
        }

        $prices = collect($response->json('data', []))
            ->map(fn (mixed $row): float => (float) (is_array($row) ? ($row['adv']['price'] ?? 0) : 0))
            ->filter(fn (float $price): bool => $price > 0)
            ->values();

        if ($prices->isEmpty()) {
            return null;
        }

        return round((float) $prices->avg(), 4);
    }

    private function positiveRate(mixed $rate): ?float
    {
        if ($rate === null || $rate === '' || ! is_numeric($rate) || (float) $rate <= 0) {
            return null;
        }

        return (float) $rate;
    }

    /**
     * @return ?array{text: string, tone: string}
     */
    private function gap(?float $usdt, ?float $usd): ?array
    {
        if ($usdt === null || $usd === null || $usd <= 0) {
            return null;
        }

        $percent = (($usdt - $usd) / $usd) * 100;
        $tone = $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat');
        $sign = $percent > 0 ? '+' : '';

        return [
            'text' => $sign.number_format($percent, 2, '.', '').'%',
            'tone' => $tone,
        ];
    }
}
