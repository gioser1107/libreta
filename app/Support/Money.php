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
}
