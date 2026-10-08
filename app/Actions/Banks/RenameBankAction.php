<?php

namespace App\Actions\Banks;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenameBankAction
{
    public function execute(User $user, int $bankId, string $name): Bank
    {
        abort_unless(auth()->id() === $user->id, 403);

        $name = Bank::validatedName($user, $name, $bankId);

        try {
            return DB::transaction(function () use ($user, $bankId, $name) {
                $bank = Bank::query()
                    ->ownedBy($user)
                    ->lockForUpdate()
                    ->find($bankId);

                abort_if($bank === null, 404);

                $bank->name = $name;
                $bank->save();

                return $bank;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'Ya tienes un banco con ese nombre.',
            ]);
        }
    }
}
