<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersByMonth;
use App\Models\Expense;
use App\Models\Income;
use App\Services\Ledger\MonthBalanceService;
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

    public function mount(): void
    {
        Permissions::authorize('ingresos', 'view');
        Permissions::authorize('egresos', 'view');
        $this->mountMonth();
    }

    public function render(MonthBalanceService $balance)
    {
        $summary = $balance->summarize(auth()->user(), $this->year, $this->month);
        $moves = $this->movements($summary['incomes'], $summary['expenses']);

        $previous = now()->setDate($this->year, $this->month, 1)->subMonth();

        return view('livewire.month-summary', [
            'months' => Calendar::MONTHS,
            'carriedFrom' => Calendar::MONTHS[$previous->month],
            'summary' => $summary,
            'moves' => $moves,
            'recent' => $moves->take(8),
            'cards' => $this->highlightCards($moves),
            'categories' => $this->expenseCategories($summary['expenses']),
            'pace' => $this->monthPace($summary),
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
     * @param  Collection<int, Expense>  $expenses
     * @return list<array{label: string, usd: float, share: int, width: int}>
     */
    private function expenseCategories(Collection $expenses): array
    {
        $total = round((float) $expenses->sum('amount_usd'), 2);

        if ($total <= 0) {
            return [];
        }

        $grouped = $expenses
            ->groupBy('category')
            ->map(function (Collection $rows, string $category) use ($total): array {
                $usd = round((float) $rows->sum('amount_usd'), 2);

                return [
                    'label' => Expense::CATEGORIES[$category] ?? $category,
                    'usd' => $usd,
                    'share' => (int) round(($usd / $total) * 100),
                ];
            })
            ->sortByDesc('usd')
            ->values();

        $largest = (float) $grouped->max('usd');

        return $grouped
            ->map(function (array $row) use ($largest): array {
                $row['width'] = $largest > 0 ? (int) round(($row['usd'] / $largest) * 100) : 0;

                return $row;
            })
            ->all();
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
            'daily_expense' => $this->dailyExpense($expense),
            'income_count' => $summary['incomes']->count(),
            'expense_count' => $summary['expenses']->count(),
            'pending_count' => $summary['expenses']->where('status', Expense::STATUS_PENDING)->count(),
        ];
    }

    private function dailyExpense(float $expense): ?float
    {
        if ($expense <= 0) {
            return null;
        }

        $start = now()->setDate($this->year, $this->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();
        $today = now()->startOfDay();

        if ($today->lt($start)) {
            return null;
        }

        $days = $today->gt($end) ? $end->day : $today->day;

        return round($expense / $days, 2);
    }
}
