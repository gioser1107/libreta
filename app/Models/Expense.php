<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

#[Fillable([
    'user_id',
    'bank_id',
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
    'payment_method',
    'status',
    'notes',
])]
class Expense extends Model
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
        'comida' => 'Comida',
        'transporte' => 'Transporte',
        'servicios' => 'Servicios',
        'vivienda' => 'Vivienda',
        'salud' => 'Salud',
        'educacion' => 'Educación',
        'hogar' => 'Hogar',
        'ocio' => 'Ocio',
        'otro' => 'Otro',
    ];

    public const PAY_CASH = 'efectivo';

    public const PAY_ZELLE = 'zelle';

    public const PAY_TRANSFER = 'transferencia';

    public const PAY_PAGO_MOVIL = 'pago_movil';

    public const PAY_PUNTO = 'punto';

    public const PAY_CARD = 'tarjeta';

    public const PAYMENT_METHODS = [
        self::PAY_CASH => 'Efectivo',
        self::PAY_ZELLE => 'Zelle',
        self::PAY_TRANSFER => 'Transferencia',
        self::PAY_PAGO_MOVIL => 'Pago móvil',
        self::PAY_PUNTO => 'Punto',
        self::PAY_CARD => 'Tarjeta',
    ];

    public const STATUS_PAID = 'paid';

    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_PAID => 'Pagado',
        self::STATUS_PENDING => 'Por pagar',
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

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public static function usesBank(?string $method): bool
    {
        return $method !== null && array_key_exists($method, self::PAYMENT_METHODS);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(User $user): array
    {
        return [
            'occurred_on' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'currency' => ['required', Rule::in(array_keys(self::CURRENCIES))],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999.99'],
            'payment_method' => ['nullable', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')->where('user_id', $user->id)],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
