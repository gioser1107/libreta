<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

#[Fillable([
    'user_id',
    'from_bank_id',
    'divisa_place_id',
    'occurred_on',
    'paid_currency',
    'paid_amount',
    'paid_amount_ves',
    'paid_amount_usd',
    'received_currency',
    'received_amount',
    'received_amount_ves',
    'received_amount_usd',
    'usd_rate_to_ves',
    'usd_bcv_rate_id',
    'spread_usd',
    'spread_ves',
    'notes',
])]
class CurrencyExchange extends Model
{
    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'paid_amount' => 'decimal:2',
            'paid_amount_ves' => 'decimal:2',
            'paid_amount_usd' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'received_amount_ves' => 'decimal:2',
            'received_amount_usd' => 'decimal:2',
            'usd_rate_to_ves' => 'decimal:6',
            'spread_usd' => 'decimal:2',
            'spread_ves' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function divisaPlace(): BelongsTo
    {
        return $this->belongsTo(DivisaPlace::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Bolívares por dólar de esta operación, cuando un lado es Bs y el otro $.
     */
    public function parallelRate(): ?string
    {
        $paid = (float) $this->paid_amount;
        $received = (float) $this->received_amount;

        $rate = match (true) {
            $this->paid_currency === Income::CURRENCY_VES && $this->received_currency === Income::CURRENCY_USD && $received > 0 => $paid / $received,
            $this->paid_currency === Income::CURRENCY_USD && $this->received_currency === Income::CURRENCY_VES && $paid > 0 => $received / $paid,
            default => null,
        };

        if ($rate === null) {
            return null;
        }

        return number_format($rate, 2, ',', '.');
    }

    public function spreadSentence(): string
    {
        $spread = (float) $this->spread_usd;
        $money = Money::format(abs($spread), Income::CURRENCY_USD);

        if (abs($spread) < 0.005) {
            $result = 'igual al BCV';
        } elseif ($spread > 0) {
            $result = "perdiste {$money}";
        } else {
            $result = "ganaste {$money}";
        }

        $parallel = $this->parallelRate();
        if ($parallel === null) {
            return $result;
        }

        $bcv = number_format((float) $this->usd_rate_to_ves, 2, ',', '.');

        return "tasa {$parallel} · BCV {$bcv} · {$result}";
    }

    public function placeName(): string
    {
        return $this->divisaPlace?->name ?? 'Divisa';
    }

    public function legsLabel(): string
    {
        return Money::format($this->paid_amount, $this->paid_currency)
            .' → '
            .Money::format($this->received_amount, $this->received_currency);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(User $user): array
    {
        $bank = ['required', 'integer', Rule::exists('banks', 'id')->where('user_id', $user->id)];
        $currency = ['required', Rule::in(array_keys(Income::CURRENCIES))];
        $amount = ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999.99'];

        return [
            'from_bank_id' => $bank,
            'divisa_place_id' => ['required', 'integer', Rule::exists('divisa_places', 'id')->where('user_id', $user->id)],
            'occurred_on' => ['required', 'date'],
            'paid_currency' => $currency,
            'paid_amount' => $amount,
            'received_currency' => $currency,
            'received_amount' => $amount,
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'from_bank_id.required' => 'Elige el banco de los bolívares.',
            'from_bank_id.exists' => 'Ese banco no está en tu lista.',
            'divisa_place_id.required' => 'Elige dónde quedaron las divisas.',
            'divisa_place_id.exists' => 'Ese lugar no está en tu lista.',
            'occurred_on.required' => 'Elige la fecha.',
            'occurred_on.date' => 'La fecha no es válida.',
            'paid_currency.required' => 'Elige la moneda que pagaste.',
            'paid_currency.in' => 'Esa moneda no está permitida.',
            'received_currency.required' => 'Elige la moneda que recibiste.',
            'received_currency.in' => 'Esa moneda no está permitida.',
            'paid_amount.required' => 'Escribe cuánto pagaste.',
            'paid_amount.gt' => 'El monto debe ser mayor a 0.',
            'paid_amount.decimal' => 'El monto acepta hasta 2 decimales.',
            'received_amount.required' => 'Escribe cuánto recibiste.',
            'received_amount.gt' => 'El monto debe ser mayor a 0.',
            'received_amount.decimal' => 'El monto acepta hasta 2 decimales.',
        ];
    }
}
