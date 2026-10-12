<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteRecurringEntryAction;
use App\Actions\Ledger\SaveRecurringEntryAction;
use App\Exceptions\LedgerException;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringEntry;
use App\Services\Ledger\MoneyQuoteService;
use App\Services\Ledger\PostRecurringEntries;
use Carbon\CarbonInterface;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecurringEntries extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public bool $confirmingRemoval = false;

    public string $kind = RecurringEntry::KIND_EXPENSE;

    public string $concept = '';

    public string $category = 'vivienda';

    public string $currency = Income::CURRENCY_USD;

    public string $amount = '';

    public string $bank_id = '';

    public string $payment_method = '';

    public string $status = Expense::STATUS_PAID;

    public string $day_of_month = '1';

    public string $notes = '';

    public function mount(PostRecurringEntries $poster): void
    {
        abort_unless(auth()->check(), 403);
        $poster->postFor(auth()->user());
    }

    public function updatedKind(): void
    {
        $categories = RecurringEntry::categoriesFor($this->kind);

        if (! array_key_exists($this->category, $categories)) {
            $this->category = (string) array_key_first($categories);
        }

        if ($this->kind === RecurringEntry::KIND_INCOME) {
            $this->payment_method = '';
            $this->status = Expense::STATUS_PAID;
        }
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    public function save(SaveRecurringEntryAction $save, PostRecurringEntries $poster): void
    {
        $save->execute(auth()->user(), $this->payload(), $this->editingId);
        $poster->postFor(auth()->user());
        $this->resetForm();
    }

    public function edit(int $entryId): void
    {
        $entry = RecurringEntry::query()->ownedBy(auth()->user())->findOrFail($entryId);
        $this->editingId = $entry->id;
        $this->confirmingRemoval = false;
        $this->kind = $entry->kind;
        $this->concept = $entry->concept;
        $this->category = $entry->category;
        $this->currency = $entry->currency;
        $this->amount = rtrim(rtrim(number_format((float) $entry->amount, 2, '.', ''), '0'), '.');
        $this->bank_id = $entry->bank_id === null ? '' : (string) $entry->bank_id;
        $this->payment_method = (string) ($entry->payment_method ?? '');
        $this->status = $entry->status;
        $this->day_of_month = (string) $entry->day_of_month;
        $this->notes = (string) ($entry->notes ?? '');
        $this->showModal = true;
        $this->resetErrorBag();
    }

    public function askRemoval(): void
    {
        if ($this->editingId === null) {
            return;
        }

        RecurringEntry::query()->ownedBy(auth()->user())->findOrFail($this->editingId);
        $this->confirmingRemoval = true;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemoval = false;
    }

    public function delete(DeleteRecurringEntryAction $delete): void
    {
        if ($this->editingId === null) {
            return;
        }

        try {
            $delete->execute(auth()->user(), $this->editingId);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('removal', 'No se pudo borrar. Intenta de nuevo.');

            return;
        }

        $this->resetForm();
    }

    public function render(MoneyQuoteService $quotes)
    {
        $user = auth()->user();
        $today = now('America/Caracas')->startOfDay();
        $rows = RecurringEntry::query()
            ->ownedBy($user)
            ->with([
                'bank',
                'posts' => fn ($query) => $query->where('period', $today->format('Y-m')),
            ])
            ->orderBy('day_of_month')
            ->orderBy('id')
            ->get()
            ->map(fn (RecurringEntry $entry): array => $this->present($entry, $quotes, $today));

        return view('livewire.recurring-entries', [
            'incomeRows' => $rows->where('kind', RecurringEntry::KIND_INCOME)->values()->all(),
            'expenseRows' => $rows->where('kind', RecurringEntry::KIND_EXPENSE)->values()->all(),
            'baseline' => $this->baseline($rows->all()),
            'banks' => Bank::query()->ownedBy($user)->orderBy('name')->orderBy('id')->get(),
            'kinds' => RecurringEntry::KINDS,
            'categories' => RecurringEntry::categoriesFor($this->kind),
            'currencies' => Income::CURRENCIES,
            'methods' => Expense::PAYMENT_METHODS,
            'statuses' => Expense::STATUSES,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'kind' => $this->kind,
            'concept' => $this->concept,
            'category' => $this->category,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'bank_id' => $this->bank_id,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'day_of_month' => $this->day_of_month,
            'notes' => $this->notes,
        ];
    }

    private function resetForm(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->confirmingRemoval = false;
        $this->kind = RecurringEntry::KIND_EXPENSE;
        $this->concept = '';
        $this->category = 'vivienda';
        $this->currency = Income::CURRENCY_USD;
        $this->amount = '';
        $this->bank_id = '';
        $this->payment_method = '';
        $this->status = Expense::STATUS_PAID;
        $this->day_of_month = '1';
        $this->notes = '';
        $this->resetErrorBag();
    }

    /**
     * @return array{
     *     id: int,
     *     kind: string,
     *     concept: string,
     *     meta: string,
     *     amount: float,
     *     currency: string,
     *     usd: float|null,
     *     active: bool,
     *     badge: string,
     *     badge_class: string
     * }
     */
    private function present(RecurringEntry $entry, MoneyQuoteService $quotes, CarbonInterface $today): array
    {
        [$badge, $badgeClass] = $this->badge($entry, $today);
        $meta = ['Día '.$entry->day_of_month, $entry->categoryLabel()];

        if ($entry->bank !== null) {
            $meta[] = $entry->bank->name;
        }

        return [
            'id' => $entry->id,
            'kind' => $entry->kind,
            'concept' => $entry->concept,
            'meta' => implode(' · ', $meta),
            'amount' => round((float) $entry->amount, 2),
            'currency' => $entry->currency,
            'usd' => $entry->active ? $this->usd($entry, $quotes, $today) : null,
            'active' => $entry->active,
            'badge' => $badge,
            'badge_class' => $badgeClass,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function badge(RecurringEntry $entry, CarbonInterface $today): array
    {
        if (! $entry->active) {
            return ['Pausado', ''];
        }

        $posted = $entry->posts->isNotEmpty();

        if ($posted && $entry->kind === RecurringEntry::KIND_EXPENSE && $entry->status === Expense::STATUS_PENDING) {
            return ['Por pagar', 'is-pending'];
        }

        if ($posted) {
            return ['Anotado', 'is-paid'];
        }

        if ($today->day < min($entry->day_of_month, $today->daysInMonth)) {
            return ['', ''];
        }

        return ['Sin anotar', 'is-pending'];
    }

    private function usd(RecurringEntry $entry, MoneyQuoteService $quotes, CarbonInterface $today): ?float
    {
        try {
            $quoted = $quotes->quote($today->toDateString(), $entry->currency, $entry->amount);
        } catch (LedgerException) {
            return null;
        }

        return round((float) $quoted['amount_usd'], 2);
    }

    /**
     * @param  list<array{kind: string, usd: float|null, active: bool}>  $rows
     * @return array{
     *     ready: bool,
     *     income_usd: float,
     *     expense_usd: float,
     *     leftover_usd: float,
     *     income_caption: string,
     *     expense_caption: string,
     *     note: string|null
     * }
     */
    private function baseline(array $rows): array
    {
        $income = 0.0;
        $expense = 0.0;
        $unquoted = 0;
        $incomeCount = 0;
        $expenseCount = 0;

        foreach ($rows as $row) {
            if ($row['kind'] === RecurringEntry::KIND_INCOME) {
                $incomeCount++;
            } else {
                $expenseCount++;
            }

            if (! $row['active']) {
                continue;
            }

            if ($row['usd'] === null) {
                $unquoted++;

                continue;
            }

            if ($row['kind'] === RecurringEntry::KIND_INCOME) {
                $income += $row['usd'];
            } else {
                $expense += $row['usd'];
            }
        }

        $income = round($income, 2);
        $expense = round($expense, 2);
        $note = null;

        if ($unquoted > 0) {
            $note = 'Falta la tasa BCV de algunos movimientos.';
        } elseif ($expense > $income) {
            $note = 'Los gastos fijos superan los ingresos.';
        }

        return [
            'ready' => $unquoted === 0 || $income > 0 || $expense > 0,
            'income_usd' => $income,
            'expense_usd' => $expense,
            'leftover_usd' => round($income - $expense, 2),
            'income_caption' => $this->caption($incomeCount, 'ingreso', 'ingresos'),
            'expense_caption' => $this->caption($expenseCount, 'gasto', 'gastos'),
            'note' => $note,
        ];
    }

    private function caption(int $count, string $one, string $many): string
    {
        if ($count === 0) {
            return 'Ninguno';
        }

        return $count === 1 ? '1 '.$one : $count.' '.$many;
    }
}
