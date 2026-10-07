<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\User;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Support\Facades\DB;

class UpdateExpenseAction
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, int $expenseId, array $input): Expense
    {
        LedgerActor::authorize($user, 'egresos', 'edit');
        $data = LedgerInput::expense($input);

        return DB::transaction(function () use ($user, $expenseId, $data) {
            $expense = Expense::query()
                ->ownedBy($user)
                ->lockForUpdate()
                ->find($expenseId);

            abort_if($expense === null, 404);

            $quoted = $this->quotes->quote($data['occurred_on'], $data['currency'], $data['amount']);
            $expense->fill([
                'occurred_on' => $data['occurred_on'],
                'concept' => $data['concept'],
                'category' => $data['category'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'notes' => $data['notes'],
                ...$quoted,
            ])->save();

            return $expense;
        });
    }
}
