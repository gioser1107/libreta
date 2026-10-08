<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

#[Fillable([
    'user_id',
    'kind',
    'concept',
    'category',
    'currency',
    'amount',
    'bank_id',
    'payment_method',
    'status',
    'notes',
    'day_of_month',
    'active',
])]
class RecurringEntry extends Model
{
    public const KIND_INCOME = 'income';

    public const KIND_EXPENSE = 'expense';

    public const KINDS = [
        self::KIND_INCOME => 'Ingreso',
        self::KIND_EXPENSE => 'Egreso',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'day_of_month' => 'integer',
            'active' => 'boolean',
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

    public function posts(): HasMany
    {
        return $this->hasMany(RecurringPost::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function categoryLabel(): string
    {
        return self::categoriesFor($this->kind)[$this->category] ?? $this->category;
    }

    /**
     * @return array<string, string>
     */
    public static function categoriesFor(string $kind): array
    {
        return $kind === self::KIND_INCOME ? Income::CATEGORIES : Expense::CATEGORIES;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(User $user, string $kind): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'concept' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(self::categoriesFor($kind)))],
            'currency' => ['required', Rule::in(array_keys(Income::CURRENCIES))],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999.99'],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')->where('user_id', $user->id)],
            'payment_method' => ['nullable', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'status' => ['required', Rule::in(array_keys(Expense::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'day_of_month' => ['required', 'integer', 'between:1,31'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'kind.required' => 'Elige si es ingreso o egreso.',
            'kind.in' => 'Ese tipo no existe.',
            'concept.required' => 'Escribe el concepto.',
            'category.required' => 'Elige una categoría.',
            'category.in' => 'Esa categoría no existe.',
            'currency.required' => 'Elige la moneda.',
            'currency.in' => 'Esa moneda no está permitida.',
            'amount.required' => 'Escribe el monto.',
            'amount.gt' => 'El monto debe ser mayor a 0.',
            'amount.decimal' => 'El monto acepta hasta 2 decimales.',
            'bank_id.exists' => 'Ese banco no está en tu lista.',
            'payment_method.in' => 'Ese medio de pago no existe.',
            'status.required' => 'Indica si ya está pagado.',
            'status.in' => 'Ese estado no existe.',
            'day_of_month.required' => 'Elige el día del mes.',
            'day_of_month.between' => 'El día tiene que estar entre 1 y 31.',
        ];
    }
}
