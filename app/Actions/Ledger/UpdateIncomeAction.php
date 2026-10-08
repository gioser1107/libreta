<?php

namespace App\Actions\Ledger;

use App\Models\Income;
use App\Models\User;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Support\Facades\DB;

class UpdateIncomeAction
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, int $incomeId, array $input): Income
    {
        LedgerActor::authorize($user, 'ingresos', 'edit');
        $data = LedgerInput::income($user, $input);

        return DB::transaction(function () use ($user, $incomeId, $data) {
            $income = Income::query()
                ->ownedBy($user)
                ->lockForUpdate()
                ->find($incomeId);

            abort_if($income === null, 404);

            $quoted = $this->quotes->quote($data['occurred_on'], $data['currency'], $data['amount']);
            $income->fill([
                'bank_id' => $data['bank_id'],
                'occurred_on' => $data['occurred_on'],
                'concept' => $data['concept'],
                'category' => $data['category'],
                'notes' => $data['notes'],
                ...$quoted,
            ])->save();

            return $income;
        });
    }
}
