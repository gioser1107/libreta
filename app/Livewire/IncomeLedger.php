<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteIncomeAction;
use App\Actions\Ledger\RecordIncomeAction;
use App\Actions\Ledger\UpdateIncomeAction;
use App\Exceptions\LedgerException;
use App\Livewire\Concerns\FiltersByMonth;
use App\Models\Bank;
use App\Models\Income;
use App\Services\Ledger\MoneyQuoteService;
use App\Services\Ledger\PostRecurringEntries;
use App\Support\Calendar;
use App\Support\Permissions;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Title('Ingresos')]
class IncomeLedger extends Component
{
    use FiltersByMonth;

    public string $search = '';

    public string $filterCategory = 'all';

    #[Url]
    public ?int $open = null;

    public bool $showModal = false;

    public bool $confirmingRemoval = false;

    public ?int $editingId = null;

    public string $concept = '';

    public string $category = 'sueldo';

    public string $occurred_on = '';

    public string $currency = Income::CURRENCY_VES;

    public string $amount = '';

    public string $bank_id = '';

    public string $notes = '';

    public function mount(PostRecurringEntries $poster): void
    {
        Permissions::authorize('ingresos', 'view');
        $this->mountMonth();
        $this->occurred_on = $this->dateInViewedMonth();
        $poster->postFor(auth()->user());
        $this->openFromUrl();
    }

    public function openModal(): void
    {
        Permissions::authorize('ingresos', 'create');
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function edit(int $incomeId): void
    {
        Permissions::authorize('ingresos', 'edit');
        $income = Income::query()->ownedBy(auth()->user())->findOrFail($incomeId);
        $this->fillIncome($income);
        $this->showModal = true;
    }

    public function askRemoval(): void
    {
        Permissions::authorize('ingresos', 'delete');
        $this->confirmingRemoval = true;
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemoval = false;
    }

    public function save(RecordIncomeAction $record, UpdateIncomeAction $update): void
    {
        $this->confirmingRemoval = false;
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

    public function delete(int $incomeId, DeleteIncomeAction $delete): void
    {
        try {
            $delete->execute(auth()->user(), $incomeId);
            $this->closeModal();
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('removal', 'No se pudo borrar. Intenta de nuevo.');
        }
    }

    public function render(MoneyQuoteService $quotes)
    {
        $user = auth()->user();
        $start = now()->setDate($this->year, $this->month, 1);
        $term = trim(str_replace(['%', '_'], '', $this->search));
        $base = Income::query()
            ->ownedBy($user)
            ->when($term === '', fn ($query) => $query->whereBetween('occurred_on', [$start->copy()->startOfMonth()->toDateString(), $start->copy()->endOfMonth()->toDateString()]))
            ->when($term !== '', fn ($query) => $query->where('concept', 'like', '%'.$term.'%'))
            ->when($this->filterCategory !== 'all', fn ($query) => $query->where('category', $this->filterCategory));

        return view('livewire.income-ledger', [
            'rows' => (clone $base)->with('bank')->orderByDesc('occurred_on')->orderByDesc('id')->get(),
            'months' => Calendar::MONTHS,
            'categories' => Income::CATEGORIES,
            'currencies' => Income::CURRENCIES,
            'banks' => Bank::query()->ownedBy($user)->orderBy('name')->orderBy('id')->get(),
            'preview' => $this->preview($quotes),
            'monthUsd' => round((float) (clone $base)->sum('amount_usd'), 2),
            'monthVes' => round((float) (clone $base)->sum('amount_ves'), 2),
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
            'bank_id' => $this->bank_id,
            'notes' => $this->notes,
        ];
    }

    private function resetForm(): void
    {
        $this->open = null;
        $this->editingId = null;
        $this->confirmingRemoval = false;
        $this->concept = '';
        $this->category = 'sueldo';
        $this->occurred_on = $this->dateInViewedMonth();
        $this->currency = Income::CURRENCY_VES;
        $this->amount = '';
        $this->bank_id = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    private function openFromUrl(): void
    {
        if ($this->open === null) {
            return;
        }

        if (! Permissions::check(auth()->user(), 'ingresos', 'edit')) {
            $this->open = null;

            return;
        }

        $income = Income::query()->ownedBy(auth()->user())->find($this->open);

        if ($income === null) {
            $this->open = null;

            return;
        }

        $this->fillIncome($income);
        $this->showModal = true;
    }

    private function fillIncome(Income $income): void
    {
        $this->open = $income->id;
        $this->editingId = $income->id;
        $this->concept = $income->concept;
        $this->category = $income->category;
        $this->occurred_on = $income->occurred_on->toDateString();
        $this->currency = $income->currency;
        $this->amount = (string) $income->amount;
        $this->bank_id = $income->bank_id === null ? '' : (string) $income->bank_id;
        $this->notes = (string) ($income->notes ?? '');
        $this->confirmingRemoval = false;
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
