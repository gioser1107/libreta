<?php

namespace App\Actions\Ledger;

use App\Models\Income;
use App\Models\User;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Support\Facades\DB;

class RecordIncomeAction
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, array $input): Income
    {
        LedgerActor::authorize($user, 'ingresos', 'create');
        $data = LedgerInput::income($user, $input);
        $quoted = $this->quotes->quote($data['occurred_on'], $data['currency'], $data['amount']);

        return DB::transaction(function () use ($user, $data, $quoted) {
            return Income::query()->create([
                'user_id' => $user->id,
                'bank_id' => $data['bank_id'],
                'occurred_on' => $data['occurred_on'],
                'concept' => $data['concept'],
                'category' => $data['category'],
                'notes' => $data['notes'],
                ...$quoted,
            ]);
        });
    }
}
