<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteExpenseBudgetAction;
use App\Actions\Ledger\SaveExpenseBudgetAction;
use App\Livewire\Concerns\FiltersByMonth;
use App\Models\Expense;
use App\Models\ExpenseBudget;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use App\Services\Ledger\BankBalanceService;
use App\Services\Ledger\MonthBalanceService;
use App\Services\Ledger\PostRecurringEntries;
use App\Support\Calendar;
use App\Support\Money;
use App\Support\Permissions;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Resumen')]
class MonthSummary extends Component
{
    use FiltersByMonth;

    public string $budgetCategory = 'comida';

    public string $budgetAmount = '';

    public function mount(PostRecurringEntries $poster): void
    {
        Permissions::authorize('ingresos', 'view');
        Permissions::authorize('egresos', 'view');
        $this->mountMonth();
        $poster->postFor(auth()->user());
    }

    public function saveBudget(SaveExpenseBudgetAction $save): void
    {
        $save->execute(auth()->user(), $this->budgetCategory, $this->budgetAmount);
        $this->budgetAmount = '';
    }

    public function clearBudget(string $category, DeleteExpenseBudgetAction $delete): void
    {
        $delete->execute(auth()->user(), $category);
    }

    public function render(MonthBalanceService $balance, BankBalanceService $banks)
    {
        $user = auth()->user();
        $summary = $balance->summarize($user, $this->year, $this->month);
        $ledger = $this->movements($summary['incomes'], $summary['expenses']);
        $moves = $ledger
            ->concat($this->transferMoves($user, $summary['from'], $summary['to']))
            ->sortByDesc('stamp')
            ->values();
        $accounts = $banks->forUser($user);

        $previous = now()->setDate($this->year, $this->month, 1)->subMonth();

        return view('livewire.month-summary', [
            'months' => Calendar::MONTHS,
            'carriedFrom' => Calendar::MONTHS[$previous->month],
            'summary' => $summary,
            'moves' => $moves,
            'recent' => $moves->take(8),
            'cards' => $this->highlightCards($ledger),
            'incomeCategories' => $this->incomeCategories($summary['incomes']),
            'categories' => $this->expenseCategories(
                $summary['expenses'],
                ExpenseBudget::query()->ownedBy($user)->get(),
            ),
            'pace' => $this->monthPace($summary),
            'accounts' => $accounts,
            'budgetCategories' => Expense::CATEGORIES,
        ])->layout('layouts.app');
    }

    /**
     * @param  Collection<int, Income>  $incomes
     * @param  Collection<int, Expense>  $expenses
     * @return Collection<int, array{
     *     id: int,
     *     kind: string,
     *     stamp: string,
     *     when: string,
     *     concept: string,
     *     meta: string,
     *     usd: float,
     *     currency: string,
     *     native: string,
     *     href: string
     * }>
     */
    private function movements(Collection $incomes, Collection $expenses): Collection
    {
        $incoming = $incomes->map(fn (Income $row): array => $this->movement(
            $row->id,
            'in',
            $row->occurred_on->format('Y-m-d').sprintf('%08d', $row->id),
            $row->occurred_on->translatedFormat('j M'),
            $row->concept,
            $this->metaWithBank(Income::CATEGORIES[$row->category] ?? $row->category, $row->bank?->name),
            (float) $row->amount_usd,
            $row->currency,
            Money::format($row->amount, $row->currency),
            route('incomes.index', [
                'month' => $this->month,
                'year' => $this->year,
                'open' => $row->id,
            ]),
        ));

        $outgoing = $expenses->map(function (Expense $row): array {
            $meta = Expense::CATEGORIES[$row->category] ?? $row->category;
            $meta = $this->metaWithBank($meta, $row->bank?->name);
            if ($row->status === Expense::STATUS_PENDING) {
                $meta .= ' · Por pagar';
            }

            return $this->movement(
                $row->id,
                'out',
                $row->occurred_on->format('Y-m-d').sprintf('%08d', $row->id),
                $row->occurred_on->translatedFormat('j M'),
                $row->concept,
                $meta,
                (float) $row->amount_usd,
                $row->currency,
                Money::format($row->amount, $row->currency),
                route('expenses.index', [
                    'month' => $this->month,
                    'year' => $this->year,
                    'open' => $row->id,
                ]),
            );
        });

        return $incoming->concat($outgoing)->sortByDesc('stamp')->values();
    }

    /**
     * @return array{
     *     id: int,
     *     kind: string,
     *     stamp: string,
     *     when: string,
     *     concept: string,
     *     meta: string,
     *     usd: float,
     *     currency: string,
     *     native: string,
     *     href: string
     * }
     */
    private function movement(
        int $id,
        string $kind,
        string $stamp,
        string $when,
        string $concept,
        string $meta,
        float $usd,
        string $currency,
        string $native,
        string $href,
    ): array {
        return [
            'id' => $id,
            'kind' => $kind,
            'stamp' => $stamp,
            'when' => $when,
            'concept' => $concept,
            'meta' => $meta,
            'usd' => $usd,
            'currency' => $currency,
            'native' => $native,
            'href' => $href,
        ];
    }

    private function metaWithBank(string $meta, ?string $bank): string
    {
        if ($bank === null || $bank === '') {
            return $meta;
        }

        return $meta.' · '.$bank;
    }

    /**
     * @param  Collection<int, array{kind: string, usd: float}>  $moves
     * @return list<array{label: string, empty: string, move: array<string, mixed>|null}>
     */
    private function highlightCards(Collection $moves): array
    {
        $incomes = $moves->where('kind', 'in')->values();
        $expenses = $moves->where('kind', 'out')->values();

        return [
            [
                'label' => 'Último ingreso',
                'empty' => 'Sin ingresos',
                'move' => $incomes->first(),
            ],
            [
                'label' => 'Último egreso',
                'empty' => 'Sin egresos',
                'move' => $expenses->first(),
            ],
            [
                'label' => 'Mayor gasto',
                'empty' => 'Sin egresos',
                'move' => $expenses->sortByDesc('usd')->first(),
            ],
            [
                'label' => 'Mayor ingreso',
                'empty' => 'Sin ingresos',
                'move' => $incomes->sortByDesc('usd')->first(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Income>  $incomes
     * @return list<array{key: string, label: string, usd: float, share: int, width: int}>
     */
    private function incomeCategories(Collection $incomes): array
    {
        $earned = $incomes
            ->groupBy('category')
            ->map(fn (Collection $rows): float => round((float) $rows->sum('amount_usd'), 2));

        if ($earned->isEmpty()) {
            return [];
        }

        $total = round((float) $earned->sum(), 2);
        $largest = (float) $earned->max();

        return $earned
            ->map(function (float $usd, string $category) use ($total, $largest): array {
                return [
                    'key' => $category,
                    'label' => Income::CATEGORIES[$category] ?? $category,
                    'usd' => round($usd, 2),
                    'share' => $total > 0 ? (int) round(($usd / $total) * 100) : 0,
                    'width' => $largest > 0 ? (int) round(($usd / $largest) * 100) : 0,
                ];
            })
            ->sortByDesc('usd')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @param  Collection<int, ExpenseBudget>  $budgets
     * @return list<array{key: string, label: string, usd: float, share: int, width: int, limit: ?float, over: bool}>
     */
    private function expenseCategories(Collection $expenses, Collection $budgets): array
    {
        $spent = $expenses
            ->groupBy('category')
            ->map(fn (Collection $rows): float => round((float) $rows->sum('amount_usd'), 2));
        $limits = $budgets->mapWithKeys(fn (ExpenseBudget $budget): array => [
            $budget->category => round((float) $budget->limit_usd, 2),
        ]);
        $keys = $spent->keys()->merge($limits->keys())->unique();

        if ($keys->isEmpty()) {
            return [];
        }

        $total = round((float) $spent->sum(), 2);
        $largest = (float) $spent->max();

        return $keys
            ->map(function (string $category) use ($spent, $limits, $total, $largest): array {
                $usd = round((float) ($spent[$category] ?? 0), 2);
                $limit = $limits->has($category) ? (float) $limits[$category] : null;

                return [
                    'key' => $category,
                    'label' => Expense::CATEGORIES[$category] ?? $category,
                    'usd' => $usd,
                    'share' => $total > 0 ? (int) round(($usd / $total) * 100) : 0,
                    'width' => $largest > 0 ? (int) round(($usd / $largest) * 100) : 0,
                    'limit' => $limit,
                    'over' => $limit !== null && $usd > $limit,
                ];
            })
            ->sortByDesc('usd')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     kind: string,
     *     stamp: string,
     *     when: string,
     *     concept: string,
     *     meta: string,
     *     usd: float,
     *     currency: string,
     *     native: string,
     *     href: string
     * }>
     */
    private function transferMoves(User $user, string $from, string $to): Collection
    {
        return Transfer::query()
            ->ownedBy($user)
            ->with(['fromBank', 'toBank'])
            ->whereBetween('occurred_on', [$from, $to])
            ->get()
            ->map(fn (Transfer $transfer): array => $this->movement(
                $transfer->id,
                'move',
                $transfer->occurred_on->format('Y-m-d').sprintf('%08d', $transfer->id),
                $transfer->occurred_on->translatedFormat('j M'),
                ($transfer->fromBank?->name ?? 'Banco').' → '.($transfer->toBank?->name ?? 'Banco'),
                'Traspaso',
                (float) $transfer->amount_usd,
                $transfer->currency,
                Money::format($transfer->amount, $transfer->currency),
                route('banks'),
            ));
    }

    /**
     * @param  array{
     *     income_usd: float,
     *     expense_usd: float,
     *     balance_usd: float,
     *     incomes: Collection<int, Income>,
     *     expenses: Collection<int, Expense>
     * }  $summary
     * @return array{
     *     caption: ?string,
     *     daily_income: ?float,
     *     daily_expense: ?float,
     *     income_count: int,
     *     expense_count: int,
     *     pending_count: int
     * }
     */
    private function monthPace(array $summary): array
    {
        $income = $summary['income_usd'];
        $expense = $summary['expense_usd'];

        $caption = null;
        if ($income > 0) {
            $kept = (int) round(($summary['balance_usd'] / $income) * 100);
            $caption = $kept >= 0
                ? "Quedó el {$kept}% de lo ingresado"
                : 'Los egresos superaron los ingresos';
        } elseif ($expense > 0) {
            $caption = 'Este mes solo hubo egresos';
        }

        return [
            'caption' => $caption,
            'daily_income' => $this->dailyAverage($income),
            'daily_expense' => $this->dailyAverage($expense),
            'income_count' => $summary['incomes']->count(),
            'expense_count' => $summary['expenses']->count(),
            'pending_count' => $summary['expenses']->where('status', Expense::STATUS_PENDING)->count(),
        ];
    }

    private function dailyAverage(float $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        $start = now()->setDate($this->year, $this->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();
        $today = now()->startOfDay();

        if ($today->lt($start)) {
            return null;
        }

        $days = $today->gt($end) ? $end->day : $today->day;

        return round($total / $days, 2);
    }
}
