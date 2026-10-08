<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

#[Fillable([
    'user_id',
    'from_bank_id',
    'to_bank_id',
    'occurred_on',
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
class Transfer extends Model
{
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

    public function fromBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function toBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'to_bank_id');
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
        $bank = ['required', 'integer', Rule::exists('banks', 'id')->where('user_id', $user->id)];

        return [
            'from_bank_id' => $bank,
            'to_bank_id' => [...$bank, 'different:from_bank_id'],
            'occurred_on' => ['required', 'date'],
            'currency' => ['required', Rule::in(array_keys(Income::CURRENCIES))],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'from_bank_id.required' => 'Elige el banco de origen.',
            'from_bank_id.exists' => 'Ese banco no está en tu lista.',
            'to_bank_id.required' => 'Elige el banco de destino.',
            'to_bank_id.exists' => 'Ese banco no está en tu lista.',
            'to_bank_id.different' => 'El destino tiene que ser otro banco.',
            'occurred_on.required' => 'Elige la fecha.',
            'occurred_on.date' => 'La fecha no es válida.',
            'currency.required' => 'Elige la moneda.',
            'currency.in' => 'Esa moneda no está permitida.',
            'amount.required' => 'Escribe el monto.',
            'amount.gt' => 'El monto debe ser mayor a 0.',
            'amount.decimal' => 'El monto acepta hasta 2 decimales.',
        ];
    }
}
