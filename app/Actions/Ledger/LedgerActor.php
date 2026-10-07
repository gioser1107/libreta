<?php

namespace App\Actions\Ledger;

use App\Models\User;
use App\Support\Permissions;

class LedgerActor
{
    public static function authorize(User $user, string $module, string $action): void
    {
        abort_unless(auth()->id() === $user->id, 403);
        Permissions::authorize($module, $action);
    }
}
