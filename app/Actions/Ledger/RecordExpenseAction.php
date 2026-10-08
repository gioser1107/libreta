<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\User;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Support\Facades\DB;

class RecordExpenseAction
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, array $input): Expense
    {
        LedgerActor::authorize($user, 'egresos', 'create');
        $data = LedgerInput::expense($user, $input);
        $quoted = $this->quotes->quote($data['occurred_on'], $data['currency'], $data['amount']);

        return DB::transaction(function () use ($user, $data, $quoted) {
            return Expense::query()->create([
                'user_id' => $user->id,
                'bank_id' => $data['bank_id'],
                'occurred_on' => $data['occurred_on'],
                'concept' => $data['concept'],
                'category' => $data['category'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'notes' => $data['notes'],
                ...$quoted,
            ]);
        });
    }
}
