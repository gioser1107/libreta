<?php

namespace App\Actions\Banks;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateBankAction
{
    public function execute(User $user, string $name): Bank
    {
        abort_unless(auth()->id() === $user->id, 403);

        $name = Bank::validatedName($user, $name);

        try {
            return Bank::query()->create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'Ya tienes un banco con ese nombre.',
            ]);
        }
    }
}
