<?php

namespace App\Actions\Ledger;

use App\Models\Transfer;
use App\Models\User;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Support\Facades\DB;

class RecordTransferAction
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, array $input): Transfer
    {
        abort_unless(auth()->id() === $user->id, 403);

        $input['notes'] = filled($input['notes'] ?? null) ? trim((string) $input['notes']) : null;
        $data = validator($input, Transfer::rules($user), Transfer::messages())->validate();
        $quoted = $this->quotes->quote($data['occurred_on'], $data['currency'], $data['amount']);

        return DB::transaction(function () use ($user, $data, $quoted) {
            return Transfer::query()->create([
                'user_id' => $user->id,
                'from_bank_id' => $data['from_bank_id'],
                'to_bank_id' => $data['to_bank_id'],
                'occurred_on' => $data['occurred_on'],
                'notes' => $data['notes'],
                ...$quoted,
            ]);
        });
    }
}
