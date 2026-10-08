<?php

namespace App\Livewire;

use App\Services\CalculatorQuoteService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Calculadora')]
class CurrencyCalculator extends Component
{
    /**
     * @var array{
     *     rates: array{USD: ?float, EUR: ?float, USDT_BUY: ?float, USDT_SELL: ?float},
     *     labels: array{USD: ?string, EUR: ?string, USDT_BUY: string, USDT_SELL: string},
     *     available: bool,
     *     error: ?string
     * }
     */
    public array $quote = [];

    public function mount(CalculatorQuoteService $quotes): void
    {
        $this->quote = $quotes->quote();
    }

    public function refreshRates(CalculatorQuoteService $quotes): void
    {
        $this->quote = $quotes->quote(fresh: true);

        $this->dispatch('calculator-rates-refreshed', quote: $this->quote);
    }

    public function render(CalculatorQuoteService $quotes): View
    {
        $usd = $this->quote['rates']['USD'] ?? null;

        return view('livewire.currency-calculator', [
            'hero' => $quotes->formatHero($usd),
            'vesPlaceholder' => $usd === null ? '0.00' : number_format($usd, 2, '.', ','),
            'gaps' => $quotes->gaps($this->quote['rates'] ?? []),
        ])->layout('layouts.app');
    }
}
