<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\ExpenseBudget;
use App\Models\User;

class DeleteExpenseBudgetAction
{
    public function execute(User $user, string $category): void
    {
        abort_unless(auth()->id() === $user->id, 403);
        abort_unless(array_key_exists($category, Expense::CATEGORIES), 404);

        ExpenseBudget::query()->ownedBy($user)->where('category', $category)->delete();
    }
}
