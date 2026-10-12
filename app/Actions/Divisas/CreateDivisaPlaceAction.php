<?php

namespace App\Actions\Divisas;

use App\Models\DivisaPlace;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateDivisaPlaceAction
{
    public function execute(User $user, string $name): DivisaPlace
    {
        abort_unless(auth()->id() === $user->id, 403);

        $name = DivisaPlace::validatedName($user, $name);

        try {
            return DivisaPlace::query()->create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'Ya tienes un lugar con ese nombre.',
            ]);
        }
    }
}
