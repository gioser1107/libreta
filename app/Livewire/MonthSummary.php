<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersByMonth;
use App\Models\Expense;
use App\Models\Income;
use App\Services\Ledger\MonthBalanceService;
use App\Support\Calendar;
use App\Support\Permissions;
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

        $incoming = $summary['incomes']->map(fn (Income $row): array => [
            'kind' => 'in',
            'stamp' => $row->occurred_on->format('Y-m-d').sprintf('%08d', $row->id),
            'when' => $row->occurred_on->format('d/m/Y'),
            'day' => $row->occurred_on->toDateString(),
            'concept' => $row->concept,
            'meta' => Income::CATEGORIES[$row->category] ?? $row->category,
            'usd' => (float) $row->amount_usd,
            'href' => route('incomes.index', ['month' => $this->month, 'year' => $this->year]),
        ]);

        $outgoing = $summary['expenses']->map(function (Expense $row): array {
            $meta = Expense::CATEGORIES[$row->category] ?? $row->category;
            if ($row->status === Expense::STATUS_PENDING) {
                $meta .= ' · Por pagar';
            }

            return [
                'kind' => 'out',
                'stamp' => $row->occurred_on->format('Y-m-d').sprintf('%08d', $row->id),
                'when' => $row->occurred_on->format('d/m/Y'),
                'day' => $row->occurred_on->toDateString(),
                'concept' => $row->concept,
                'meta' => $meta,
                'usd' => (float) $row->amount_usd,
                'href' => route('expenses.index', ['month' => $this->month, 'year' => $this->year]),
            ];
        });

        return view('livewire.month-summary', [
            'months' => Calendar::MONTHS,
            'summary' => $summary,
            'moves' => $incoming->concat($outgoing)->sortByDesc('stamp')->values(),
        ])->layout('layouts.app');
    }
}
