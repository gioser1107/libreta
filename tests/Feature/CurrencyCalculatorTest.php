<?php

namespace Tests\Feature;

use App\Livewire\CurrencyCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('calculator'))->assertRedirect(route('login'));
    }

    public function test_calculator_shows_the_bcv_dollar_and_the_usdt_gap(): void
    {
        $this->bcv('2026-10-08', 874.7321, 979.0788922);
        $this->fakeBinance(buy: ['1014', '1016'], sell: ['1000', '1002']);

        $buyGap = $this->gapText(1015, 874.7321);
        $sellGap = $this->gapText(1001, 874.7321);

        $this->actingAs($this->usuario())
            ->get(route('calculator'))
            ->assertOk()
            ->assertSee('Calculadora')
            ->assertSee('Dólar')
            ->assertSee('874,73')
            ->assertSee('bolívares')
            ->assertSee('8 de Octubre de 2026')
            ->assertSee('Brecha BCV vs USDT')
            ->assertSee($buyGap)
            ->assertSee($sellGap)
            ->assertSee('USDT compra')
            ->assertSee('Restablecer');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'friendly/c2c/adv/search')
            && $request->data()['tradeType'] === 'BUY'
            && $request->data()['fiat'] === 'VES'
            && $request->data()['asset'] === 'USDT');
        Http::assertSent(fn ($request): bool => ($request->data()['tradeType'] ?? null) === 'SELL');
    }

    public function test_a_signed_in_user_without_ledger_permission_can_open_the_calculator(): void
    {
        $this->bcv('2026-10-08', 100, 120);
        $this->fakeBinance(buy: ['110'], sell: ['90']);

        $this->actingAs(User::factory()->create())
            ->get(route('calculator'))
            ->assertOk()
            ->assertSee('100,00');
    }

    public function test_binance_failure_still_shows_the_bcv_rate(): void
    {
        $this->bcv('2026-10-08', 874.7321, 979);
        Http::preventStrayRequests();
        Http::fake([
            'https://p2p.binance.com/*' => Http::response(['code' => 'unavailable'], 503),
        ]);

        Livewire::actingAs($this->usuario())
            ->test(CurrencyCalculator::class)
            ->assertSee('874,73')
            ->assertSet('quote.rates.USDT_BUY', null)
            ->assertSet('quote.rates.USDT_SELL', null);
    }

    public function test_missing_bcv_rate_says_the_rate_is_unavailable(): void
    {
        $this->fakeBinance(buy: ['1014'], sell: ['1000']);

        $this->actingAs($this->usuario())
            ->get(route('calculator'))
            ->assertOk()
            ->assertSee('Tasa no disponible actualmente');
    }

    public function test_refresh_loads_a_new_usdt_average(): void
    {
        $this->bcv('2026-10-08', 100, 120);
        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function () use (&$attempts) {
            $attempts++;
            $price = $attempts <= 2 ? '10' : '40';

            return Http::response([
                'code' => '000000',
                'data' => [
                    ['adv' => ['price' => $price]],
                ],
            ]);
        });

        Livewire::actingAs($this->usuario())
            ->test(CurrencyCalculator::class)
            ->assertSet('quote.rates.USDT_BUY', 10)
            ->assertSet('quote.rates.USDT_SELL', 10)
            ->call('refreshRates')
            ->assertSet('quote.rates.USDT_BUY', 40)
            ->assertSet('quote.rates.USDT_SELL', 40);
    }

    /**
     * @param  list<string>  $buy
     * @param  list<string>  $sell
     */
    private function fakeBinance(array $buy, array $sell): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) use ($buy, $sell) {
            $prices = ($request->data()['tradeType'] ?? null) === 'SELL' ? $sell : $buy;

            return Http::response([
                'code' => '000000',
                'data' => array_map(
                    fn (string $price): array => ['adv' => ['price' => $price]],
                    $prices,
                ),
            ]);
        });
    }

    private function gapText(float $usdt, float $usd): string
    {
        $percent = (($usdt - $usd) / $usd) * 100;
        $sign = $percent > 0 ? '+' : '';

        return $sign.number_format($percent, 2, '.', '').'%';
    }
}
