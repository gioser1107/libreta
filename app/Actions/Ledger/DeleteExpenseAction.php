<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteExpenseAction
{
    public function execute(User $user, int $expenseId): void
    {
        LedgerActor::authorize($user, 'egresos', 'delete');

        DB::transaction(function () use ($user, $expenseId) {
            $expense = Expense::query()
                ->ownedBy($user)
                ->lockForUpdate()
                ->find($expenseId);

            abort_if($expense === null, 404);
            $expense->delete();
        });
    }
}
