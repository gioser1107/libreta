<?php

namespace App\Actions\Divisas;

use App\Models\DivisaPlace;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenameDivisaPlaceAction
{
    public function execute(User $user, int $placeId, string $name): DivisaPlace
    {
        abort_unless(auth()->id() === $user->id, 403);

        $name = DivisaPlace::validatedName($user, $name, $placeId);

        try {
            return DB::transaction(function () use ($user, $placeId, $name) {
                $place = DivisaPlace::query()
                    ->ownedBy($user)
                    ->lockForUpdate()
                    ->find($placeId);

                abort_if($place === null, 404);

                $place->name = $name;
                $place->save();

                return $place;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'Ya tienes un lugar con ese nombre.',
            ]);
        }
    }
}
