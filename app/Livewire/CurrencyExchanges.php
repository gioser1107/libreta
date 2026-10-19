<?php

namespace App\Livewire;

use App\Actions\Divisas\CreateDivisaPlaceAction;
use App\Actions\Divisas\DeleteDivisaPlaceAction;
use App\Actions\Divisas\RenameDivisaPlaceAction;
use App\Actions\Ledger\DeleteCurrencyExchangeAction;
use App\Actions\Ledger\RecordCurrencyExchangeAction;
use App\Exceptions\LedgerException;
use App\Models\Bank;
use App\Models\CurrencyExchange;
use App\Models\DivisaPlace;
use App\Models\Income;
use App\Services\Ledger\DivisaBalanceService;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CurrencyExchanges extends Component
{
    public string $from_bank_id = '';

    public string $divisa_place_id = '';

    public string $occurred_on = '';

    public string $paid_currency = Income::CURRENCY_VES;

    public string $paid_amount = '';

    public string $received_currency = Income::CURRENCY_USD;

    public string $received_amount = '';

    public ?int $confirmingRemovalId = null;

    public bool $showModal = false;

    public string $placeName = '';

    public ?int $editingPlaceId = null;

    public ?int $confirmingPlaceRemovalId = null;

    public bool $showPlaceModal = false;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
        $this->occurred_on = now('America/Caracas')->toDateString();
    }

    public function save(RecordCurrencyExchangeAction $record): void
    {
        try {
            $record->execute(auth()->user(), [
                'from_bank_id' => $this->from_bank_id,
                'divisa_place_id' => $this->divisa_place_id,
                'occurred_on' => $this->occurred_on,
                'paid_currency' => $this->paid_currency,
                'paid_amount' => $this->paid_amount,
                'received_currency' => $this->received_currency,
                'received_amount' => $this->received_amount,
                'notes' => null,
            ]);
        } catch (ValidationException|HttpException $exception) {
            throw $exception;
        } catch (LedgerException $exception) {
            $this->addError('paid_amount', $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('paid_amount', 'No se pudo guardar. Intenta de nuevo.');

            return;
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

    public function askRemoval(int $exchangeId): void
    {
        CurrencyExchange::query()->ownedBy(auth()->user())->findOrFail($exchangeId);
        $this->confirmingRemovalId = $exchangeId;
        $this->resetErrorBag();
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemovalId = null;
    }

    public function delete(int $exchangeId, DeleteCurrencyExchangeAction $delete): void
    {
        try {
            $delete->execute(auth()->user(), $exchangeId);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('removal', 'No se pudo borrar. Intenta de nuevo.');

            return;
        }

        $this->confirmingRemovalId = null;
    }

    public function openPlaceModal(): void
    {
        $this->resetPlaceForm();
        $this->showPlaceModal = true;
    }

    public function closePlaceModal(): void
    {
        $this->resetPlaceForm();
    }

    public function savePlace(CreateDivisaPlaceAction $create, RenameDivisaPlaceAction $rename): void
    {
        if ($this->editingPlaceId) {
            $rename->execute(auth()->user(), $this->editingPlaceId, $this->placeName);
        } else {
            $create->execute(auth()->user(), $this->placeName);
        }

        $this->resetPlaceForm();
    }

    public function editPlace(int $placeId): void
    {
        $place = DivisaPlace::query()->ownedBy(auth()->user())->findOrFail($placeId);
        $this->editingPlaceId = $place->id;
        $this->placeName = $place->name;
        $this->confirmingPlaceRemovalId = null;
        $this->showPlaceModal = true;
        $this->resetErrorBag();
    }

    public function askPlaceRemoval(int $placeId): void
    {
        DivisaPlace::query()->ownedBy(auth()->user())->findOrFail($placeId);
        $this->confirmingPlaceRemovalId = $placeId;
        $this->showPlaceModal = true;
        $this->resetErrorBag();
    }

    public function cancelPlaceRemoval(): void
    {
        $this->confirmingPlaceRemovalId = null;
    }

    public function deletePlace(int $placeId, DeleteDivisaPlaceAction $delete): void
    {
        $delete->execute(auth()->user(), $placeId);

        if ($this->editingPlaceId === $placeId) {
            $this->resetPlaceForm();

            return;
        }

        $this->confirmingPlaceRemovalId = null;
        $this->showPlaceModal = false;
    }

    public function render(DivisaBalanceService $divisas, MoneyQuoteService $quotes)
    {
        $user = auth()->user();
        $spread = round((float) CurrencyExchange::query()->ownedBy($user)->sum('spread_usd'), 2);

        return view('livewire.currency-exchanges', [
            'banks' => Bank::query()->ownedBy($user)->orderBy('name')->orderBy('id')->get(),
            'holdings' => $divisas->forUser($user),
            'currencies' => Income::CURRENCIES,
            'exchanges' => CurrencyExchange::query()
                ->ownedBy($user)
                ->with(['fromBank', 'divisaPlace'])
                ->orderByDesc('occurred_on')
                ->orderByDesc('id')
                ->limit(12)
                ->get(),
            'spreadUsd' => $spread,
            'preview' => $this->showModal ? $this->preview($quotes) : null,
        ]);
    }

    /**
     * @return array{spread: float, bcv: float}|null
     */
    private function preview(MoneyQuoteService $quotes): ?array
    {
        if ($this->paid_currency === $this->received_currency || $this->paid_amount === '' || $this->received_amount === '' || $this->occurred_on === '') {
            return null;
        }

        try {
            $paid = $quotes->quote($this->occurred_on, $this->paid_currency, $this->paid_amount);
            $received = $quotes->quote($this->occurred_on, $this->received_currency, $this->received_amount);
        } catch (LedgerException) {
            return null;
        }

        return [
            'spread' => round((float) $paid['amount_usd'] - (float) $received['amount_usd'], 2),
            'bcv' => (float) $paid['usd_rate_to_ves'],
        ];
    }

    private function resetForm(): void
    {
        $this->from_bank_id = '';
        $this->divisa_place_id = '';
        $this->occurred_on = now('America/Caracas')->toDateString();
        $this->paid_currency = Income::CURRENCY_VES;
        $this->paid_amount = '';
        $this->received_currency = Income::CURRENCY_USD;
        $this->received_amount = '';
        $this->confirmingRemovalId = null;
        $this->showModal = false;
        $this->resetErrorBag();
    }

    private function resetPlaceForm(): void
    {
        $this->editingPlaceId = null;
        $this->confirmingPlaceRemovalId = null;
        $this->placeName = '';
        $this->showPlaceModal = false;
        $this->resetErrorBag();
    }
}
