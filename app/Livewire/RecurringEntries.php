<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteRecurringEntryAction;
use App\Actions\Ledger\SaveRecurringEntryAction;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringEntry;
use App\Services\Ledger\PostRecurringEntries;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecurringEntries extends Component
{
    public ?int $editingId = null;

    public ?int $confirmingRemovalId = null;

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

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
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
        $this->confirmingRemovalId = null;
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
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function askRemoval(int $entryId): void
    {
        RecurringEntry::query()->ownedBy(auth()->user())->findOrFail($entryId);
        $this->confirmingRemovalId = $entryId;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemovalId = null;
    }

    public function delete(int $entryId, DeleteRecurringEntryAction $delete): void
    {
        try {
            $delete->execute(auth()->user(), $entryId);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('removal', 'No se pudo borrar. Intenta de nuevo.');

            return;
        }

        if ($this->editingId === $entryId) {
            $this->resetForm();

            return;
        }

        $this->confirmingRemovalId = null;
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.recurring-entries', [
            'entries' => RecurringEntry::query()->ownedBy($user)->with('bank')->orderBy('day_of_month')->orderBy('id')->get(),
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
        $this->editingId = null;
        $this->confirmingRemovalId = null;
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
}
