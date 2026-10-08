<?php

namespace App\Actions\Banks;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteBankAction
{
    public function execute(User $user, int $bankId): void
    {
        abort_unless(auth()->id() === $user->id, 403);

        DB::transaction(function () use ($user, $bankId) {
            $bank = Bank::query()
                ->ownedBy($user)
                ->lockForUpdate()
                ->find($bankId);

            abort_if($bank === null, 404);

            if ($bank->hasMovements()) {
                throw ValidationException::withMessages([
                    'removal' => 'Este banco tiene movimientos. Puedes cambiarle el nombre.',
                ]);
            }

            $bank->delete();
        });
    }
}
