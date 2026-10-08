<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\RecurringEntry;
use App\Models\User;

class SaveRecurringEntryAction
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, array $input, ?int $entryId = null): RecurringEntry
    {
        abort_unless(auth()->id() === $user->id, 403);

        $kind = is_string($input['kind'] ?? null) ? $input['kind'] : '';
        $input['concept'] = trim((string) ($input['concept'] ?? ''));
        $input['notes'] = filled($input['notes'] ?? null) ? trim((string) $input['notes']) : null;
        $input['bank_id'] = filled($input['bank_id'] ?? null) ? $input['bank_id'] : null;

        if ($kind === RecurringEntry::KIND_INCOME) {
            $input['payment_method'] = null;
            $input['status'] = Expense::STATUS_PAID;
        } else {
            $method = $input['payment_method'] ?? null;
            $input['payment_method'] = is_string($method) && $method !== '' ? $method : null;
            $input['status'] = filled($input['status'] ?? null) ? $input['status'] : Expense::STATUS_PAID;
        }

        $data = validator($input, RecurringEntry::rules($user, $kind), RecurringEntry::messages())->validate();

        $entry = $entryId === null
            ? new RecurringEntry(['user_id' => $user->id, 'active' => true])
            : RecurringEntry::query()->ownedBy($user)->findOrFail($entryId);

        $entry->fill([
            'kind' => $data['kind'],
            'concept' => $data['concept'],
            'category' => $data['category'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'bank_id' => $data['bank_id'],
            'payment_method' => $data['payment_method'],
            'status' => $data['status'],
            'notes' => $data['notes'],
            'day_of_month' => $data['day_of_month'],
        ]);
        $entry->save();

        return $entry;
    }
}
