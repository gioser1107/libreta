<?php

namespace App\Services;

use App\Models\BcvRate;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BcvOfficialRatesSyncService
{
    /**
     * Lee las tasas oficiales del BCV y las guarda para la fecha indicada.
     * El comando nocturno pasa el día siguiente en Caracas: esa fila no se usa
     * para movimientos de hoy.
     *
     * @return array{usd: ?float, eur: ?float, errors: list<string>}
     */
    public function syncForDate(?\DateTimeInterface $date = null): array
    {
        $date = $date ? Carbon::instance($date)->toDateString() : now()->toDateString();
        $errors = [];
        $usd = $this->fetchRateFromBcvPage('dolar');
        if ($usd === null) {
            $errors[] = 'No se pudo obtener USD (dólar) desde bcv.org.ve';
        }
        $eur = $this->fetchRateFromBcvPage('euro');
        if ($eur === null) {
            $eur = $this->fetchRateFromBcvPage('eur');
        }
        if ($eur === null) {
            $errors[] = 'No se pudo obtener EUR desde bcv.org.ve';
        }

        $usdId = Currency::query()->where('code', 'USD')->value('id');
        $eurId = Currency::query()->where('code', 'EUR')->value('id');

        if ($usd !== null && $usdId) {
            $this->upsertRate((int) $usdId, $usd, $date);
        }
        if ($eur !== null && $eurId) {
            $this->upsertRate((int) $eurId, $eur, $date);
        }

        return [
            'usd' => $usd,
            'eur' => $eur,
            'errors' => $errors,
        ];
    }

    protected function upsertRate(int $currencyId, float $rate, string $date): void
    {
        BcvRate::query()->updateOrCreate(
            [
                'currency_id' => $currencyId,
                'date_effective' => $date,
            ],
            [
                'rate_to_ves' => round($rate, 6),
            ]
        );
    }

    public function fetchRateFromBcvPage(string $elementId): ?float
    {
        if (! in_array($elementId, ['dolar', 'euro', 'eur'], true)) {
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(45)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; +https://bcv.org.ve)',
                ])
                ->get('https://www.bcv.org.ve');

            if ($response->failed()) {
                Log::warning('BCV HTTP fallido', ['status' => $response->status()]);

                return null;
            }

            $dom = new \DOMDocument;
            @$dom->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $targetElement = $xpath->query("//*[@id='{$elementId}']//strong");

            if ($targetElement->length < 1) {
                return null;
            }

            $raw = trim($targetElement->item(0)->nodeValue ?? '');
            $raw = str_replace(',', '.', $raw);
            $rate = (float) preg_replace('/[^0-9.]/', '', $raw);

            return $rate > 0 ? $rate : null;
        } catch (\Throwable $e) {
            Log::error('BCV scrape error', ['id' => $elementId, 'message' => $e->getMessage()]);

            return null;
        }
    }
}
