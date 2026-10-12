<?php

namespace App\Actions\Divisas;

use App\Models\DivisaPlace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDivisaPlaceAction
{
    public function execute(User $user, int $placeId): void
    {
        abort_unless(auth()->id() === $user->id, 403);

        DB::transaction(function () use ($user, $placeId) {
            $place = DivisaPlace::query()
                ->ownedBy($user)
                ->lockForUpdate()
                ->find($placeId);

            abort_if($place === null, 404);

            if ($place->hasMovements()) {
                throw ValidationException::withMessages([
                    'removal' => 'Este lugar tiene cambios. Puedes cambiarle el nombre.',
                ]);
            }

            $place->delete();
        });
    }
}
