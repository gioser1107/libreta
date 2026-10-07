<?php

namespace App\Services\Ledger;

use App\Exceptions\LedgerException;
use App\Models\BcvRate;

class MoneyQuoteService
{
    /**
     * Convierte el monto con la tasa de esa fecha (o la última publicada antes).
     * El multiplicador es la tasa de dinero: 2 decimales, redondeo normal.
     * Nunca toma una fecha futura.
     *
     * @return array{
     *     bcv_rate_id: int,
     *     usd_bcv_rate_id: int,
     *     rate_to_ves: string,
     *     usd_rate_to_ves: string,
     *     amount: string,
     *     amount_ves: string,
     *     amount_usd: string,
     *     currency: string
     * }
     */
    public function quote(string $onDate, string $currency, float|string $amount): array
    {
        $currency = strtoupper($currency);
        if (! in_array($currency, ['VES', 'USD', 'EUR'], true)) {
            throw new LedgerException('Moneda no soportada.');
        }

        $amount = round((float) $amount, 2);
        if ($amount <= 0) {
            throw new LedgerException('El monto debe ser mayor a 0.');
        }

        $usd = BcvRate::forCodeOnOrBefore('USD', $onDate);
        if ($usd === null) {
            throw new LedgerException('No hay tasa BCV en dólares para esa fecha.');
        }

        $usdMoney = $usd->moneyRateToVes();
        if ($usdMoney <= 0) {
            throw new LedgerException('La tasa BCV no es válida.');
        }

        if ($currency === 'EUR') {
            $eur = BcvRate::forCodeOnOrBefore('EUR', $onDate);
            if ($eur === null) {
                throw new LedgerException('No hay tasa BCV en euros para esa fecha.');
            }

            $eurMoney = $eur->moneyRateToVes();
            if ($eurMoney <= 0) {
                throw new LedgerException('La tasa BCV no es válida.');
            }

            $amountVes = round($amount * $eurMoney, 2);
            $amountUsd = round($amountVes / $usdMoney, 2);

            return $this->pack($eur, $usd, 'EUR', $amount, $amountVes, $amountUsd, $eurMoney, $usdMoney);
        }

        if ($currency === 'USD') {
            $amountUsd = $amount;
            $amountVes = round($amountUsd * $usdMoney, 2);

            return $this->pack($usd, $usd, 'USD', $amount, $amountVes, $amountUsd, $usdMoney, $usdMoney);
        }

        $amountVes = $amount;
        $amountUsd = round($amountVes / $usdMoney, 2);

        return $this->pack($usd, $usd, 'VES', $amount, $amountVes, $amountUsd, 1.0, $usdMoney);
    }

    /**
     * @return array{
     *     bcv_rate_id: int,
     *     usd_bcv_rate_id: int,
     *     rate_to_ves: string,
     *     usd_rate_to_ves: string,
     *     amount: string,
     *     amount_ves: string,
     *     amount_usd: string,
     *     currency: string
     * }
     */
    private function pack(
        BcvRate $rate,
        BcvRate $usdRate,
        string $currency,
        float $amount,
        float $amountVes,
        float $amountUsd,
        float $rateToVes,
        float $usdRateToVes,
    ): array {
        return [
            'bcv_rate_id' => (int) $rate->id,
            'usd_bcv_rate_id' => (int) $usdRate->id,
            'rate_to_ves' => $this->rate($rateToVes),
            'usd_rate_to_ves' => $this->rate($usdRateToVes),
            'amount' => $this->money($amount),
            'amount_ves' => $this->money($amountVes),
            'amount_usd' => $this->money($amountUsd),
            'currency' => $currency,
        ];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function rate(float $value): string
    {
        return number_format($value, 6, '.', '');
    }
}
