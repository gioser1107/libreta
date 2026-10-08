<?php

namespace App\Services\Ledger;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthBalanceService
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     income_usd: float,
     *     income_ves: float,
     *     expense_usd: float,
     *     expense_ves: float,
     *     expense_paid_usd: float,
     *     expense_pending_usd: float,
     *     balance_usd: float,
     *     balance_ves: float,
     *     opening_usd: float,
     *     opening_ves: float,
     *     available_usd: float,
     *     available_ves: float,
     *     incomes: Collection<int, Income>,
     *     expenses: Collection<int, Expense>
     * }
     */
    public function summarize(User $user, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $from = $start->toDateString();
        $to = $start->copy()->endOfMonth()->toDateString();

        $incomes = Income::query()->ownedBy($user)->whereBetween('occurred_on', [$from, $to]);
        $expenses = Expense::query()->ownedBy($user)->whereBetween('occurred_on', [$from, $to]);

        $incomeUsd = round((float) (clone $incomes)->sum('amount_usd'), 2);
        $incomeVes = round((float) (clone $incomes)->sum('amount_ves'), 2);
        $expenseUsd = round((float) (clone $expenses)->sum('amount_usd'), 2);
        $expenseVes = round((float) (clone $expenses)->sum('amount_ves'), 2);
        $paidUsd = round((float) (clone $expenses)->where('status', Expense::STATUS_PAID)->sum('amount_usd'), 2);
        $pendingUsd = round((float) (clone $expenses)->where('status', Expense::STATUS_PENDING)->sum('amount_usd'), 2);
        $balanceUsd = round($incomeUsd - $expenseUsd, 2);
        $balanceVes = round($incomeVes - $expenseVes, 2);
        $opening = $this->openingBalance($user, $from);

        return [
            'from' => $from,
            'to' => $to,
            'income_usd' => $incomeUsd,
            'income_ves' => $incomeVes,
            'expense_usd' => $expenseUsd,
            'expense_ves' => $expenseVes,
            'expense_paid_usd' => $paidUsd,
            'expense_pending_usd' => $pendingUsd,
            'balance_usd' => $balanceUsd,
            'balance_ves' => $balanceVes,
            'opening_usd' => $opening['usd'],
            'opening_ves' => $opening['ves'],
            'available_usd' => round($opening['usd'] + $balanceUsd, 2),
            'available_ves' => round($opening['ves'] + $balanceVes, 2),
            'incomes' => (clone $incomes)->with('bank')->orderByDesc('occurred_on')->orderByDesc('id')->get(),
            'expenses' => (clone $expenses)->with('bank')->orderByDesc('occurred_on')->orderByDesc('id')->get(),
        ];
    }

    /**
     * Lo que quedó al cierre del mes anterior, incluidos los meses previos.
     *
     * @return array{usd: float, ves: float}
     */
    private function openingBalance(User $user, string $from): array
    {
        $incomeUsd = round((float) Income::query()->ownedBy($user)->where('occurred_on', '<', $from)->sum('amount_usd'), 2);
        $incomeVes = round((float) Income::query()->ownedBy($user)->where('occurred_on', '<', $from)->sum('amount_ves'), 2);
        $expenseUsd = round((float) Expense::query()->ownedBy($user)->where('occurred_on', '<', $from)->sum('amount_usd'), 2);
        $expenseVes = round((float) Expense::query()->ownedBy($user)->where('occurred_on', '<', $from)->sum('amount_ves'), 2);

        return [
            'usd' => round($incomeUsd - $expenseUsd, 2),
            'ves' => round($incomeVes - $expenseVes, 2),
        ];
    }
}
