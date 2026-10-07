<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

#[Fillable([
    'user_id',
    'occurred_on',
    'concept',
    'category',
    'currency',
    'amount',
    'amount_ves',
    'amount_usd',
    'rate_to_ves',
    'usd_rate_to_ves',
    'bcv_rate_id',
    'usd_bcv_rate_id',
    'notes',
])]
class Income extends Model
{
    public const CURRENCY_VES = 'VES';

    public const CURRENCY_USD = 'USD';

    public const CURRENCY_EUR = 'EUR';

    public const CURRENCIES = [
        self::CURRENCY_VES => 'Bolívares',
        self::CURRENCY_USD => 'Dólares',
        self::CURRENCY_EUR => 'Euros',
    ];

    public const CATEGORIES = [
        'sueldo' => 'Sueldo',
        'honorarios' => 'Honorarios',
        'venta' => 'Venta',
        'transferencia' => 'Transferencia',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'amount' => 'decimal:2',
            'amount_ves' => 'decimal:2',
            'amount_usd' => 'decimal:2',
            'rate_to_ves' => 'decimal:6',
            'usd_rate_to_ves' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'occurred_on' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'currency' => ['required', Rule::in(array_keys(self::CURRENCIES))],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
