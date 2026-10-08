<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteExpenseAction;
use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\UpdateExpenseAction;
use App\Exceptions\LedgerException;
use App\Livewire\Concerns\FiltersByMonth;
use App\Models\Expense;
use App\Services\Ledger\MoneyQuoteService;
use App\Support\Calendar;
use App\Support\Permissions;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Title('Egresos')]
class ExpenseLedger extends Component
{
    use FiltersByMonth;

    public string $search = '';

    public string $filterCategory = 'all';

    #[Url]
    public string $filterStatus = 'all';

    #[Url]
    public ?int $open = null;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $concept = '';

    public string $category = 'comida';

    public string $occurred_on = '';

    public string $currency = 'USD';

    public string $amount = '';

    public string $payment_method = '';

    public string $status = 'paid';

    public string $notes = '';

    public function mount(): void
    {
        Permissions::authorize('egresos', 'view');
        $this->mountMonth();
        $this->occurred_on = $this->dateInViewedMonth();
        $this->openFromUrl();
    }

    public function openModal(): void
    {
        Permissions::authorize('egresos', 'create');
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function edit(int $expenseId): void
    {
        Permissions::authorize('egresos', 'edit');
        $expense = Expense::query()->ownedBy(auth()->user())->findOrFail($expenseId);
        $this->fillExpense($expense);
        $this->showModal = true;
    }

    public function save(RecordExpenseAction $record, UpdateExpenseAction $update): void
    {
        $payload = $this->payload();

        try {
            if ($this->editingId) {
                $update->execute(auth()->user(), $this->editingId, $payload);
            } else {
                $record->execute(auth()->user(), $payload);
            }
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (LedgerException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('amount', 'No se pudo guardar. Intenta de nuevo.');

            return;
        }

        $this->showMonthOf($this->occurred_on);
        $this->closeModal();
    }

    public function delete(int $expenseId, DeleteExpenseAction $delete): void
    {
        try {
            $delete->execute(auth()->user(), $expenseId);
            $this->closeModal();
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('search', 'No se pudo borrar. Intenta de nuevo.');
        }
    }

    public function render(MoneyQuoteService $quotes)
    {
        $user = auth()->user();
        $start = now()->setDate($this->year, $this->month, 1);
        $term = str_replace(['%', '_'], '', $this->search);
        $base = Expense::query()
            ->ownedBy($user)
            ->whereBetween('occurred_on', [$start->copy()->startOfMonth()->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->when($term !== '', fn ($query) => $query->where('concept', 'like', '%'.$term.'%'))
            ->when($this->filterCategory !== 'all', fn ($query) => $query->where('category', $this->filterCategory))
            ->when($this->filterStatus !== 'all', fn ($query) => $query->where('status', $this->filterStatus));

        return view('livewire.expense-ledger', [
            'rows' => (clone $base)->orderByDesc('occurred_on')->orderByDesc('id')->get(),
            'months' => Calendar::MONTHS,
            'categories' => Expense::CATEGORIES,
            'currencies' => Expense::CURRENCIES,
            'methods' => Expense::PAYMENT_METHODS,
            'statuses' => Expense::STATUSES,
            'preview' => $this->preview($quotes),
            'monthUsd' => round((float) (clone $base)->sum('amount_usd'), 2),
            'monthVes' => round((float) (clone $base)->sum('amount_ves'), 2),
            'pendingUsd' => round((float) (clone $base)->where('status', Expense::STATUS_PENDING)->sum('amount_usd'), 2),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'occurred_on' => $this->occurred_on,
            'concept' => $this->concept,
            'category' => $this->category,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }

    private function resetForm(): void
    {
        $this->open = null;
        $this->editingId = null;
        $this->concept = '';
        $this->category = 'comida';
        $this->occurred_on = $this->dateInViewedMonth();
        $this->currency = 'USD';
        $this->amount = '';
        $this->payment_method = '';
        $this->status = Expense::STATUS_PAID;
        $this->notes = '';
        $this->resetErrorBag();
    }

    private function openFromUrl(): void
    {
        if ($this->open === null) {
            return;
        }

        if (! Permissions::check(auth()->user(), 'egresos', 'edit')) {
            $this->open = null;

            return;
        }

        $expense = Expense::query()->ownedBy(auth()->user())->find($this->open);

        if ($expense === null) {
            $this->open = null;

            return;
        }

        $this->fillExpense($expense);
        $this->showModal = true;
    }

    private function fillExpense(Expense $expense): void
    {
        $this->open = $expense->id;
        $this->editingId = $expense->id;
        $this->concept = $expense->concept;
        $this->category = $expense->category;
        $this->occurred_on = $expense->occurred_on->toDateString();
        $this->currency = $expense->currency;
        $this->amount = (string) $expense->amount;
        $this->payment_method = (string) ($expense->payment_method ?? '');
        $this->status = $expense->status;
        $this->notes = (string) ($expense->notes ?? '');
        $this->showMonthOf($this->occurred_on);
        $this->resetErrorBag();
    }

    /**
     * @return array{rate: string, ves: string, usd: string}|null
     */
    private function preview(MoneyQuoteService $quotes): ?array
    {
        if ($this->occurred_on === '' || ! is_numeric($this->amount) || (float) $this->amount <= 0) {
            return null;
        }

        try {
            $quoted = $quotes->quote($this->occurred_on, $this->currency, $this->amount);
        } catch (LedgerException) {
            return null;
        }

        $shownRate = $this->currency === 'EUR' ? $quoted['rate_to_ves'] : $quoted['usd_rate_to_ves'];

        return [
            'rate' => number_format((float) $shownRate, 2, ',', '.'),
            'ves' => $quoted['amount_ves'],
            'usd' => $quoted['amount_usd'],
        ];
    }
}
