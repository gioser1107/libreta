<?php

namespace App\Actions\Ledger;

use App\Models\RecurringEntry;
use App\Models\User;

class DeleteRecurringEntryAction
{
    public function execute(User $user, int $entryId): void
    {
        abort_unless(auth()->id() === $user->id, 403);

        $entry = RecurringEntry::query()->ownedBy($user)->findOrFail($entryId);
        $entry->delete();
    }
}
