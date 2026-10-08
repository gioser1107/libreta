<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'user_id',
    'name',
])]
class Bank extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function hasMovements(): bool
    {
        return $this->incomes()->exists() || $this->expenses()->exists();
    }

    public static function validatedName(User $user, string $name, ?int $exceptId = null): string
    {
        $name = self::prepareName($name);

        validator([
            'name' => $name,
        ], [
            'name' => ['required', 'string', 'max:60'],
        ], [
            'name.required' => 'Escribe el nombre del banco.',
            'name.max' => 'El nombre acepta hasta 60 caracteres.',
        ], [
            'name' => 'nombre',
        ])->validate();

        $taken = self::query()
            ->ownedBy($user)
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->get()
            ->contains(fn (self $bank): bool => Str::lower($bank->name) === Str::lower($name));

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => 'Ya tienes un banco con ese nombre.',
            ]);
        }

        return $name;
    }

    public static function prepareName(string $name): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($name));

        return is_string($collapsed) ? $collapsed : trim($name);
    }
}
