<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['currency_id', 'rate_to_ves', 'date_effective'])]
class BcvRate extends Model
{
    protected function casts(): array
    {
        return [
            'date_effective' => 'date',
            'rate_to_ves' => 'decimal:6',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Tasa para mostrar y convertir en el cuaderno: 2 decimales, redondeo normal.
     * Solo se mira el tercer decimal (≥5 sube, si no se queda).
     * 929.09083243 → 929.09; 929.098 → 929.10.
     * En bcv_rates queda la tasa oficial con todos los decimales.
     */
    public static function forMoney(int|float|string|null $rate): float
    {
        if ($rate === null || $rate === '') {
            return 0.0;
        }

        $normalized = number_format(abs((float) $rate), 6, '.', '');
        if (! is_numeric($normalized) || (float) $normalized <= 0) {
            return 0.0;
        }

        [$whole, $frac] = explode('.', $normalized, 2);
        $frac = str_pad($frac, 6, '0');
        $cents = ((int) $whole) * 100 + (int) substr($frac, 0, 2);
        if ((int) $frac[2] >= 5) {
            $cents++;
        }

        return $cents / 100;
    }

    public function moneyRateToVes(): float
    {
        return self::forMoney($this->rate_to_ves);
    }

    /**
     * Tasa de esa fecha exacta. Null si el sync no la trajo ese día.
     */
    public static function forCodeOnDate(string $code, string $onDate): ?self
    {
        $currencyId = Currency::query()->where('code', $code)->value('id');
        if (! $currencyId) {
            return null;
        }

        return static::query()
            ->where('currency_id', $currencyId)
            ->whereDate('date_effective', $onDate)
            ->first();
    }

    /**
     * Tasa vigente en $onDate: la de ese día, o la última publicada antes.
     * No usa fechas futuras (el sync de las 20:15 guarda la de mañana).
     */
    public static function forCodeOnOrBefore(string $code, string $onDate): ?self
    {
        $currencyId = Currency::query()->where('code', $code)->value('id');
        if (! $currencyId) {
            return null;
        }

        return static::query()
            ->where('currency_id', $currencyId)
            ->whereDate('date_effective', '<=', $onDate)
            ->orderByDesc('date_effective')
            ->first();
    }
}
