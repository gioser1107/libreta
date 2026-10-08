<?php

namespace App\Actions\Ledger;

use App\Models\Transfer;
use App\Models\User;

class DeleteTransferAction
{
    public function execute(User $user, int $transferId): void
    {
        abort_unless(auth()->id() === $user->id, 403);

        $transfer = Transfer::query()->ownedBy($user)->findOrFail($transferId);
        $transfer->delete();
    }
}
