<?php

namespace App\Services\Ledger;

use App\Models\Bank;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BankBalanceService
{
    /**
     * @return Collection<int, array{
     *     bank: Bank,
     *     balances: array{VES: float, USD: float, EUR: float},
     *     label: string
     * }>
     */
    public function forUser(User $user): Collection
    {
        $banks = Bank::query()->ownedBy($user)->orderBy('name')->orderBy('id')->get();
        $income = $this->grouped(Income::query()->ownedBy($user)->whereNotNull('bank_id'));
        $paid = $this->grouped(
            Expense::query()->ownedBy($user)->whereNotNull('bank_id')->where('status', Expense::STATUS_PAID),
        );
        $incoming = $this->grouped(Transfer::query()->ownedBy($user), 'to_bank_id');
        $outgoing = $this->grouped(Transfer::query()->ownedBy($user), 'from_bank_id');

        return $banks->map(function (Bank $bank) use ($income, $paid, $incoming, $outgoing): array {
            $balances = [];

            foreach (['VES' => 'opening_ves', 'USD' => 'opening_usd', 'EUR' => 'opening_eur'] as $currency => $opening) {
                $key = $bank->id.'|'.$currency;
                $balances[$currency] = round(
                    (float) $bank->{$opening}
                    + ($income[$key] ?? 0)
                    - ($paid[$key] ?? 0)
                    + ($incoming[$key] ?? 0)
                    - ($outgoing[$key] ?? 0),
                    2,
                );
            }

            return [
                'bank' => $bank,
                'balances' => $balances,
                'label' => $this->label($balances),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array{balances: array{VES: float, USD: float, EUR: float}}>  $accounts
     * @return array{VES: float, USD: float, EUR: float, label: string}
     */
    public function totals(Collection $accounts): array
    {
        $totals = ['VES' => 0.0, 'USD' => 0.0, 'EUR' => 0.0];

        foreach ($accounts as $account) {
            foreach ($totals as $currency => $amount) {
                $totals[$currency] = round($amount + $account['balances'][$currency], 2);
            }
        }

        return [
            ...$totals,
            'label' => $this->label($totals),
        ];
    }

    /**
     * @param  array{VES: float, USD: float, EUR: float}  $balances
     */
    public function label(array $balances): string
    {
        $parts = [];

        foreach (['USD', 'VES', 'EUR'] as $currency) {
            if ($balances[$currency] != 0.0) {
                $parts[] = Money::format($balances[$currency], $currency);
            }
        }

        return $parts === [] ? 'Sin saldo' : implode(' · ', $parts);
    }

    /**
     * @param  Builder<Income>|Builder<Expense>|Builder<Transfer>  $query
     * @return array<string, float>
     */
    private function grouped(Builder $query, string $bankColumn = 'bank_id'): array
    {
        $rows = (clone $query)
            ->select($bankColumn, 'currency')
            ->selectRaw('SUM(amount) as total')
            ->groupBy($bankColumn, 'currency')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$row->getAttribute($bankColumn).'|'.$row->currency] = (float) $row->total;
        }

        return $map;
    }
}
