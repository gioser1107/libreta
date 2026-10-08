<?php

namespace App\Livewire;

use App\Actions\Ledger\DeleteTransferAction;
use App\Actions\Ledger\RecordTransferAction;
use App\Exceptions\LedgerException;
use App\Models\Bank;
use App\Models\Income;
use App\Models\Transfer;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Transfers extends Component
{
    public string $from_bank_id = '';

    public string $to_bank_id = '';

    public string $occurred_on = '';

    public string $currency = Income::CURRENCY_VES;

    public string $amount = '';

    public string $notes = '';

    public ?int $confirmingRemovalId = null;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
        $this->occurred_on = now('America/Caracas')->toDateString();
    }

    public function save(RecordTransferAction $record): void
    {
        try {
            $record->execute(auth()->user(), [
                'from_bank_id' => $this->from_bank_id,
                'to_bank_id' => $this->to_bank_id,
                'occurred_on' => $this->occurred_on,
                'currency' => $this->currency,
                'amount' => $this->amount,
                'notes' => $this->notes,
            ]);
        } catch (ValidationException|HttpException $exception) {
            throw $exception;
        } catch (LedgerException $exception) {
            $this->addError('amount', $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('amount', 'No se pudo guardar. Intenta de nuevo.');

            return;
        }

        $this->resetForm();
    }

    public function askRemoval(int $transferId): void
    {
        Transfer::query()->ownedBy(auth()->user())->findOrFail($transferId);
        $this->confirmingRemovalId = $transferId;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemovalId = null;
    }

    public function delete(int $transferId, DeleteTransferAction $delete): void
    {
        try {
            $delete->execute(auth()->user(), $transferId);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('removal', 'No se pudo borrar. Intenta de nuevo.');

            return;
        }

        $this->confirmingRemovalId = null;
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.transfers', [
            'banks' => Bank::query()->ownedBy($user)->orderBy('name')->orderBy('id')->get(),
            'currencies' => Income::CURRENCIES,
            'transfers' => Transfer::query()
                ->ownedBy($user)
                ->with(['fromBank', 'toBank'])
                ->orderByDesc('occurred_on')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->from_bank_id = '';
        $this->to_bank_id = '';
        $this->occurred_on = now('America/Caracas')->toDateString();
        $this->currency = Income::CURRENCY_VES;
        $this->amount = '';
        $this->notes = '';
        $this->confirmingRemovalId = null;
        $this->resetErrorBag();
    }
}
