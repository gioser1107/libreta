<?php

namespace App\Actions\Ledger;

use App\Models\CurrencyExchange;
use App\Models\User;

class DeleteCurrencyExchangeAction
{
    public function execute(User $user, int $exchangeId): void
    {
        abort_unless(auth()->id() === $user->id, 403);

        $exchange = CurrencyExchange::query()->ownedBy($user)->find($exchangeId);
        abort_if($exchange === null, 404);
        $exchange->delete();
    }
}
