<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount, string $currency = 'USD'): string
    {
        $symbol = match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            default => 'Bs',
        };

        return $symbol.' '.number_format((float) $amount, 2, ',', '.');
    }

    /**
     * Bolívares equivalentes a esos dólares con la tasa de dinero ya redondeada a 2 decimales.
     */
    public static function vesEquivalent(float|int|string|null $dollars, float|int|string|null $rateToVes): ?float
    {
        if ($rateToVes === null || (float) $rateToVes <= 0 || (float) $dollars == 0.0) {
            return null;
        }

        return round(round((float) $dollars, 2) * (float) $rateToVes, 2);
    }
}
