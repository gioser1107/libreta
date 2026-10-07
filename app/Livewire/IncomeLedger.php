<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteIncomeAction;
use App\Actions\Ledger\RecordIncomeAction;
use App\Actions\Ledger\UpdateIncomeAction;
use App\Exceptions\LedgerException;
use App\Livewire\Concerns\FiltersByMonth;
use App\Models\Income;
use App\Services\Ledger\MoneyQuoteService;
use App\Support\Calendar;
use App\Support\Permissions;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Title('Ingresos')]
class IncomeLedger extends Component
{
    use FiltersByMonth;
    use WithPagination;

    public string $search = '';

    public string $filterCategory = 'all';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $concept = '';

    public string $category = 'sueldo';

    public string $occurred_on = '';

    public string $currency = 'USD';

    public string $amount = '';

    public string $notes = '';

    public function mount(): void
    {
        Permissions::authorize('ingresos', 'view');
        $this->mountMonth();
        $this->occurred_on = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function openModal(): void
    {
        Permissions::authorize('ingresos', 'create');
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $incomeId): void
    {
        Permissions::authorize('ingresos', 'edit');
        $income = Income::query()->ownedBy(auth()->user())->findOrFail($incomeId);
        $this->editingId = $income->id;
        $this->concept = $income->concept;
        $this->category = $income->category;
        $this->occurred_on = $income->occurred_on->toDateString();
        $this->currency = $income->currency;
        $this->amount = (string) $income->amount;
        $this->notes = (string) ($income->notes ?? '');
        $this->showModal = true;
        $this->resetErrorBag();
    }

    public function save(RecordIncomeAction $record, UpdateIncomeAction $update): void
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

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $incomeId, DeleteIncomeAction $delete): void
    {
        try {
            $delete->execute(auth()->user(), $incomeId);
            $this->showModal = false;
            $this->resetForm();
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
        $base = Income::query()
            ->ownedBy($user)
            ->whereBetween('occurred_on', [$start->copy()->startOfMonth()->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->when($term !== '', fn ($query) => $query->where('concept', 'like', '%'.$term.'%'))
            ->when($this->filterCategory !== 'all', fn ($query) => $query->where('category', $this->filterCategory));

        return view('livewire.income-ledger', [
            'rows' => (clone $base)->orderByDesc('occurred_on')->orderByDesc('id')->paginate(10),
            'months' => Calendar::MONTHS,
            'categories' => Income::CATEGORIES,
            'currencies' => Income::CURRENCIES,
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
            'notes' => $this->notes,
        ];
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->concept = '';
        $this->category = 'sueldo';
        $this->occurred_on = now()->toDateString();
        $this->currency = 'USD';
        $this->amount = '';
        $this->notes = '';
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
