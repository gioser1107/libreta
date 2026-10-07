<?php

namespace App\Actions\Ledger;

use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteIncomeAction
{
    public function execute(User $user, int $incomeId): void
    {
        LedgerActor::authorize($user, 'ingresos', 'delete');

        DB::transaction(function () use ($user, $incomeId) {
            $income = Income::query()
                ->ownedBy($user)
                ->lockForUpdate()
                ->find($incomeId);

            abort_if($income === null, 404);
            $income->delete();
        });
    }
}
