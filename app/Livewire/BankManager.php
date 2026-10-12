<?php

namespace App\Livewire;

use App\Actions\Banks\CreateBankAction;
use App\Actions\Banks\DeleteBankAction;
use App\Actions\Banks\RenameBankAction;
use App\Actions\Banks\SetBankOpeningAction;
use App\Models\Bank;
use App\Services\Ledger\BankBalanceService;
use Illuminate\Support\Collection;
use Livewire\Component;

class BankManager extends Component
{
    public string $name = '';

    public string $openingVes = '';

    public string $openingUsd = '';

    public string $openingEur = '';

    public ?int $editingId = null;

    public ?int $confirmingRemovalId = null;

    public bool $showModal = false;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function save(CreateBankAction $create, RenameBankAction $rename, SetBankOpeningAction $openings): void
    {
        $user = auth()->user();
        $amounts = $this->openings();
        $openings->validated($amounts);

        if ($this->editingId) {
            $rename->execute($user, $this->editingId, $this->name);
            $openings->execute($user, $this->editingId, $amounts);
        } else {
            $bank = $create->execute($user, $this->name);
            $openings->execute($user, $bank->id, $amounts);
        }

        $this->resetForm();
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

    public function edit(int $bankId): void
    {
        $bank = Bank::query()->ownedBy(auth()->user())->findOrFail($bankId);
        $this->editingId = $bank->id;
        $this->name = $bank->name;
        $this->openingVes = $this->amountInput($bank->opening_ves);
        $this->openingUsd = $this->amountInput($bank->opening_usd);
        $this->openingEur = $this->amountInput($bank->opening_eur);
        $this->confirmingRemovalId = null;
        $this->showModal = true;
        $this->resetErrorBag();
    }

    public function askRemoval(int $bankId): void
    {
        Bank::query()->ownedBy(auth()->user())->findOrFail($bankId);
        $this->confirmingRemovalId = $bankId;
        $this->showModal = true;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemovalId = null;
    }

    public function delete(int $bankId, DeleteBankAction $delete): void
    {
        $delete->execute(auth()->user(), $bankId);

        if ($this->editingId === $bankId) {
            $this->resetForm();

            return;
        }

        $this->confirmingRemovalId = null;
        $this->showModal = false;
    }

    public function render(BankBalanceService $balances)
    {
        $user = auth()->user();

        $accounts = $balances->forUser($user);

        return view('livewire.bank-manager', [
            'banks' => $accounts->map(fn (array $account): Bank => $account['bank']),
            'balances' => $accounts->keyBy(fn (array $account): int => $account['bank']->id),
            'totals' => $this->totals($accounts),
        ]);
    }

    /**
     * @param  Collection<int, array{bank: Bank, balances: array{VES: float, USD: float, EUR: float}, label: string}>  $accounts
     * @return array{VES: float, USD: float, EUR: float}
     */
    private function totals(Collection $accounts): array
    {
        $totals = ['VES' => 0.0, 'USD' => 0.0, 'EUR' => 0.0];

        foreach ($accounts as $account) {
            foreach ($totals as $currency => $amount) {
                $totals[$currency] = round($amount + $account['balances'][$currency], 2);
            }
        }

        return $totals;
    }

    /**
     * @return array{opening_ves: string, opening_usd: string, opening_eur: string}
     */
    private function openings(): array
    {
        return [
            'opening_ves' => $this->openingVes,
            'opening_usd' => $this->openingUsd,
            'opening_eur' => $this->openingEur,
        ];
    }

    private function amountInput(mixed $amount): string
    {
        if ((float) $amount == 0.0) {
            return '';
        }

        $formatted = number_format((float) $amount, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->confirmingRemovalId = null;
        $this->name = '';
        $this->openingVes = '';
        $this->openingUsd = '';
        $this->openingEur = '';
        $this->showModal = false;
        $this->resetErrorBag();
    }
}
