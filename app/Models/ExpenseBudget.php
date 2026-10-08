<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

#[Fillable([
    'user_id',
    'category',
    'limit_usd',
])]
class ExpenseBudget extends Model
{
    protected function casts(): array
    {
        return [
            'limit_usd' => 'decimal:2',
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
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'limit_usd' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'category.required' => 'Elige una categoría.',
            'category.in' => 'Esa categoría no existe.',
            'limit_usd.required' => 'Escribe el tope.',
            'limit_usd.gt' => 'El tope debe ser mayor a 0.',
            'limit_usd.decimal' => 'El tope acepta hasta 2 decimales.',
        ];
    }
}
