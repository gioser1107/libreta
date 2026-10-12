<?php

namespace Tests\Feature;

use App\Livewire\BankManager;
use App\Livewire\CurrencyExchanges;
use App\Livewire\MonthSummary;
use App\Models\Bank;
use App\Models\CurrencyExchange;
use App\Models\DivisaPlace;
use App\Models\User;
use App\Services\Ledger\BankBalanceService;
use App\Services\Ledger\DivisaBalanceService;
use App\Services\Ledger\MonthBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyExchangesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_exchanges(): void
    {
        Livewire::test(CurrencyExchanges::class)->assertForbidden();
    }

    public function test_banks_page_offers_divisas_outside_the_banks(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->get(route('banks'))
            ->assertOk()
            ->assertSee('Divisas')
            ->assertSee('Agrega el lugar donde guardas las divisas.')
            ->assertDontSee('Cuenta de ahorro')
            ->assertSeeLivewire(CurrencyExchanges::class);
    }

    public function test_user_names_the_place_where_divisas_are_kept(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->call('openPlaceModal')
            ->set('placeName', 'Zinli')
            ->call('savePlace')
            ->assertHasNoErrors()
            ->assertSee('Zinli')
            ->call('openPlaceModal')
            ->set('placeName', 'zinli')
            ->call('savePlace')
            ->assertHasErrors(['name' => 'Ya tienes un lugar con ese nombre.']);

        $this->assertSame(1, DivisaPlace::query()->count());
    }

    public function test_buying_dollars_above_bcv_keeps_them_in_cash_and_records_the_loss(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, 'Banesco', ['opening_ves' => 85000]);
        $cash = $this->place($user, 'Efectivo');
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $cash->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Banesco → Efectivo')
            ->assertSee('perdiste $ 8,82')
            ->assertSee('llevas perdido $ 8,82');

        $exchange = CurrencyExchange::query()->first();
        $this->assertNotNull($exchange);
        $this->assertSame($cash->id, $exchange->divisa_place_id);
        $this->assertEqualsWithDelta(8.82, (float) $exchange->spread_usd, 0.001);
        $this->assertEqualsWithDelta(7500.0, (float) $exchange->spread_ves, 0.001);

        $accounts = app(BankBalanceService::class)->forUser($user)->keyBy(fn (array $row): int => $row['bank']->id);
        $this->assertEqualsWithDelta(35000.0, $accounts[$from->id]['balances']['VES'], 0.001);
        $this->assertEqualsWithDelta(0.0, $accounts[$from->id]['balances']['USD'], 0.001);

        $holdings = collect(app(DivisaBalanceService::class)->forUser($user))->keyBy('label');
        $this->assertEqualsWithDelta(50.0, $holdings['Efectivo']['balances']['USD'], 0.001);
        $this->assertArrayNotHasKey('Binance', $holdings->all());

        $october = app(MonthBalanceService::class)->summarize($user, 2026, 10);
        $this->assertEqualsWithDelta(0.0, $october['income_usd'], 0.001);
        $this->assertEqualsWithDelta(0.0, $october['expense_usd'], 0.001);
        $this->assertEqualsWithDelta(-8.82, $october['balance_usd'], 0.001);
        $this->assertEqualsWithDelta(-8.82, $october['available_usd'], 0.001);

        $november = app(MonthBalanceService::class)->summarize($user, 2026, 11);
        $this->assertEqualsWithDelta(-8.82, $november['opening_usd'], 0.001);
        $this->assertEqualsWithDelta(0.0, $november['balance_usd'], 0.001);

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->assertSee('En el cambio perdiste $ 8,82')
            ->assertSee('Banesco → Efectivo')
            ->assertSee('Divisas')
            ->assertDontSee('Ahorro');

        $content = $this->actingAs($user)
            ->get(route('ledger.export', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Cambio', $content);
        $this->assertStringContainsString('Banesco → Efectivo', $content);
        $this->assertStringContainsString('perdiste $ 8,82', $content);
    }

    public function test_binance_and_cash_balances_stay_apart(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $bank = $this->bank($user, 'Banesco', ['opening_ves' => 200000]);
        $binance = $this->place($user, 'Binance');
        $cash = $this->place($user, 'Efectivo');
        $this->actingAs($user);

        $component = Livewire::actingAs($user)->test(CurrencyExchanges::class);

        $component
            ->set('from_bank_id', (string) $bank->id)
            ->set('divisa_place_id', (string) $binance->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasNoErrors()
            ->set('from_bank_id', (string) $bank->id)
            ->set('divisa_place_id', (string) $cash->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '10000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '10')
            ->call('save')
            ->assertHasNoErrors();

        $holdings = collect(app(DivisaBalanceService::class)->forUser($user))->keyBy('label');
        $this->assertEqualsWithDelta(50.0, $holdings['Binance']['balances']['USD'], 0.001);
        $this->assertEqualsWithDelta(10.0, $holdings['Efectivo']['balances']['USD'], 0.001);

        $accounts = app(BankBalanceService::class)->forUser($user)->keyBy(fn (array $row): int => $row['bank']->id);
        $this->assertEqualsWithDelta(0.0, $accounts[$bank->id]['balances']['USD'], 0.001);
    }

    public function test_buying_dollars_below_bcv_records_a_gain(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, 'Banesco', ['opening_ves' => 40000]);
        $binance = $this->place($user, 'Binance');
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $binance->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '40000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('ganaste $ 2,94');

        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 10);
        $this->assertEqualsWithDelta(2.94, $summary['available_usd'], 0.001);
    }

    public function test_exchange_at_the_bcv_rate_does_not_change_the_month(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $bank = $this->bank($user, 'Banesco', ['opening_ves' => 42500]);
        $cash = $this->place($user, 'Efectivo');
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $bank->id)
            ->set('divisa_place_id', (string) $cash->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '42500')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('igual al BCV');

        $accounts = app(BankBalanceService::class)->forUser($user)->keyBy(fn (array $row): int => $row['bank']->id);
        $this->assertEqualsWithDelta(0.0, $accounts[$bank->id]['balances']['VES'], 0.001);
        $this->assertEqualsWithDelta(0.0, $accounts[$bank->id]['balances']['USD'], 0.001);

        $holdings = collect(app(DivisaBalanceService::class)->forUser($user))->keyBy('label');
        $this->assertEqualsWithDelta(50.0, $holdings['Efectivo']['balances']['USD'], 0.001);

        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 10);
        $this->assertEqualsWithDelta(0.0, $summary['balance_usd'], 0.001);
    }

    public function test_the_form_previews_the_loss_before_saving(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->call('openModal')
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->assertSee('Pierdes $ 8,82 respecto al BCV');
    }

    public function test_deleting_an_exchange_puts_the_bolivars_back(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, 'Banesco', ['opening_ves' => 85000]);
        $binance = $this->place($user, 'Binance');

        $component = Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $binance->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save');

        $exchangeId = CurrencyExchange::query()->value('id');

        $component
            ->call('askRemoval', $exchangeId)
            ->call('delete', $exchangeId)
            ->assertHasNoErrors();

        $this->assertSame(0, CurrencyExchange::query()->count());
        $accounts = app(BankBalanceService::class)->forUser($user)->keyBy(fn (array $row): int => $row['bank']->id);
        $this->assertEqualsWithDelta(85000.0, $accounts[$from->id]['balances']['VES'], 0.001);

        $holdings = collect(app(DivisaBalanceService::class)->forUser($user))->keyBy('label');
        $this->assertEqualsWithDelta(0.0, $holdings['Binance']['balances']['USD'], 0.001);

        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 10);
        $this->assertEqualsWithDelta(0.0, $summary['available_usd'], 0.001);
    }

    public function test_empty_exchange_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('paid_amount', '')
            ->set('received_amount', '')
            ->call('save')
            ->assertHasErrors([
                'from_bank_id' => 'Elige el banco de los bolívares.',
                'divisa_place_id' => 'Elige dónde quedaron las divisas.',
                'paid_amount' => 'Escribe cuánto pagaste.',
                'received_amount' => 'Escribe cuánto recibiste.',
            ]);

        $this->assertSame(0, CurrencyExchange::query()->count());
    }

    public function test_exchange_in_the_same_currency_is_rejected(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, 'Banesco');
        $binance = $this->place($user, 'Binance');

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $binance->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'USD')
            ->set('paid_amount', '50')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasErrors(['received_currency' => 'Elige otra moneda. Para la misma moneda usa un traspaso.']);

        $this->assertSame(0, CurrencyExchange::query()->count());
    }

    public function test_exchange_between_dollars_and_euros_is_rejected(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, 'Banesco');
        $cash = $this->place($user, 'Efectivo');

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $cash->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'USD')
            ->set('paid_amount', '50')
            ->set('received_currency', 'EUR')
            ->set('received_amount', '40')
            ->call('save')
            ->assertHasErrors(['received_currency' => 'El cambio es entre bolívares y divisas.']);

        $this->assertSame(0, CurrencyExchange::query()->count());
    }

    public function test_exchange_rejects_another_persons_bank_and_record(): void
    {
        $user = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $own = $this->bank($user, 'Banesco');
        $binance = $this->place($user, 'Binance');
        $foreignPlace = $this->place($other, 'Binance');
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $this->bank($other, 'Mercantil')->id)
            ->set('divisa_place_id', (string) $foreignPlace->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasErrors([
                'from_bank_id' => 'Ese banco no está en tu lista.',
                'divisa_place_id' => 'Ese lugar no está en tu lista.',
            ]);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $own->id)
            ->set('divisa_place_id', (string) $binance->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save');

        $exchangeId = CurrencyExchange::query()->value('id');

        Livewire::actingAs($other)
            ->test(CurrencyExchanges::class)
            ->call('delete', $exchangeId)
            ->assertNotFound();

        $this->assertModelExists(CurrencyExchange::query()->findOrFail($exchangeId));
    }

    public function test_missing_rate_does_not_save_the_exchange(): void
    {
        $user = $this->usuario();
        $from = $this->bank($user, 'Banesco');
        $cash = $this->place($user, 'Efectivo');

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $cash->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '50000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '50')
            ->call('save')
            ->assertHasErrors(['paid_amount' => 'No hay tasa BCV en dólares para esa fecha.']);

        $this->assertSame(0, CurrencyExchange::query()->count());
    }

    public function test_bank_used_in_an_exchange_cannot_be_deleted(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, 'Banesco');
        $binance = $this->place($user, 'Binance');
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $binance->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '1000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '1')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->call('askRemoval', $from->id)
            ->call('delete', $from->id)
            ->assertHasErrors(['removal' => 'Este banco tiene movimientos. Puedes cambiarle el nombre.']);

        $this->assertModelExists($from);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->call('editPlace', $binance->id)
            ->call('askPlaceRemoval', $binance->id)
            ->call('deletePlace', $binance->id)
            ->assertHasErrors(['removal' => 'Este lugar tiene cambios. Puedes cambiarle el nombre.']);

        $this->assertModelExists($binance);
    }

    public function test_exchange_list_escapes_bank_names(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 850, 900);
        $from = $this->bank($user, '<script>alert(1)</script>');
        $cash = $this->place($user, 'Efectivo');
        $this->actingAs($user);

        Livewire::actingAs($user)
            ->test(CurrencyExchanges::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('divisa_place_id', (string) $cash->id)
            ->set('occurred_on', '2026-10-08')
            ->set('paid_currency', 'VES')
            ->set('paid_amount', '1000')
            ->set('received_currency', 'USD')
            ->set('received_amount', '1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    /**
     * @param  array<string, mixed>  $opening
     */
    private function bank(User $user, string $name, array $opening = []): Bank
    {
        return Bank::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            ...$opening,
        ]);
    }

    private function place(User $user, string $name): DivisaPlace
    {
        return DivisaPlace::query()->create([
            'user_id' => $user->id,
            'name' => $name,
        ]);
    }
}
