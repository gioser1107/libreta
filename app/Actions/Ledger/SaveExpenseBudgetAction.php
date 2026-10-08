<?php

namespace App\Actions\Ledger;

use App\Models\ExpenseBudget;
use App\Models\User;

class SaveExpenseBudgetAction
{
    public function execute(User $user, string $category, mixed $limit): ExpenseBudget
    {
        abort_unless(auth()->id() === $user->id, 403);

        $data = validator([
            'category' => $category,
            'limit_usd' => is_string($limit) ? trim($limit) : $limit,
        ], ExpenseBudget::rules(), ExpenseBudget::messages())->validate();

        return ExpenseBudget::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'category' => $data['category'],
            ],
            [
                'limit_usd' => $data['limit_usd'],
            ],
        );
    }
}
