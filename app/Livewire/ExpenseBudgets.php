<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteExpenseBudgetAction;
use App\Actions\Ledger\SaveExpenseBudgetAction;
use App\Models\Expense;
use App\Models\ExpenseBudget;
use App\Services\Ledger\MonthBalanceService;
use App\Support\Calendar;
use App\Support\Permissions;
use Illuminate\Support\Collection;
use Livewire\Component;

class ExpenseBudgets extends Component
{
    public string $budgetCategory = 'comida';

    public string $budgetAmount = '';

    public ?string $editingCategory = null;

    public bool $confirmingRemoval = false;

    public bool $showModal = false;

    public function mount(): void
    {
        Permissions::authorize('egresos', 'view');
    }

    public function openModal(): void
    {
        $this->resetForm();

        $used = ExpenseBudget::query()->ownedBy(auth()->user())->pluck('category');

        foreach (array_keys(Expense::CATEGORIES) as $category) {
            if (! $used->contains($category)) {
                $this->budgetCategory = $category;

                break;
            }
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    public function saveBudget(SaveExpenseBudgetAction $save, DeleteExpenseBudgetAction $delete): void
    {
        $user = auth()->user();
        $previous = $this->editingCategory;

        $save->execute($user, $this->budgetCategory, $this->budgetAmount);

        if ($previous !== null && $previous !== $this->budgetCategory) {
            $delete->execute($user, $previous);
        }

        $this->resetForm();
    }

    public function edit(string $category): void
    {
        $budget = ExpenseBudget::query()
            ->ownedBy(auth()->user())
            ->where('category', $category)
            ->firstOrFail();

        $this->editingCategory = $budget->category;
        $this->budgetCategory = $budget->category;
        $this->budgetAmount = $this->amountInput($budget->limit_usd);
        $this->confirmingRemoval = false;
        $this->showModal = true;
        $this->resetErrorBag();
    }

    public function askRemoval(): void
    {
        if ($this->editingCategory === null) {
            return;
        }

        ExpenseBudget::query()
            ->ownedBy(auth()->user())
            ->where('category', $this->editingCategory)
            ->firstOrFail();

        $this->confirmingRemoval = true;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemoval = false;
    }

    public function clearBudget(string $category, DeleteExpenseBudgetAction $delete): void
    {
        $delete->execute(auth()->user(), $category);

        if ($this->editingCategory === $category) {
            $this->resetForm();
        }
    }

    public function render(MonthBalanceService $balance)
    {
        $user = auth()->user();
        $today = now();
        $summary = $balance->summarize($user, $today->year, $today->month);
        $caps = $this->caps(
            $summary['expenses'],
            ExpenseBudget::query()->ownedBy($user)->get(),
        );

        return view('livewire.expense-budgets', [
            'monthLabel' => Calendar::MONTHS[$today->month],
            'caps' => $caps,
            'overview' => $this->overview($caps),
            'categories' => Expense::CATEGORIES,
        ]);
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @param  Collection<int, ExpenseBudget>  $budgets
     * @return list<array{key: string, label: string, usd: float, limit: float, width: int, over: bool}>
     */
    private function caps(Collection $expenses, Collection $budgets): array
    {
        $spent = $expenses
            ->groupBy('category')
            ->map(fn (Collection $rows): float => round((float) $rows->sum('amount_usd'), 2));

        return $budgets
            ->map(function (ExpenseBudget $budget) use ($spent): array {
                $usd = round((float) ($spent[$budget->category] ?? 0), 2);
                $limit = round((float) $budget->limit_usd, 2);

                return [
                    'key' => $budget->category,
                    'label' => Expense::CATEGORIES[$budget->category] ?? $budget->category,
                    'usd' => $usd,
                    'limit' => $limit,
                    'width' => $limit > 0 ? (int) min(100, round(($usd / $limit) * 100)) : 0,
                    'over' => $usd > $limit,
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * @param  list<array{key: string, label: string, usd: float, limit: float, width: int, over: bool}>  $caps
     * @return array{spent: float, limit: float, left: float, over: int}
     */
    private function overview(array $caps): array
    {
        $spent = round((float) array_sum(array_column($caps, 'usd')), 2);
        $limit = round((float) array_sum(array_column($caps, 'limit')), 2);

        return [
            'spent' => $spent,
            'limit' => $limit,
            'left' => round($limit - $spent, 2),
            'over' => count(array_filter($caps, fn (array $cap): bool => $cap['over'])),
        ];
    }

    private function amountInput(mixed $amount): string
    {
        $formatted = number_format((float) $amount, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function resetForm(): void
    {
        $this->showModal = false;
        $this->editingCategory = null;
        $this->confirmingRemoval = false;
        $this->budgetCategory = 'comida';
        $this->budgetAmount = '';
        $this->resetErrorBag();
    }
}
