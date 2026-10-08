<?php

namespace Tests\Feature;

use App\Actions\Ledger\RecordTransferAction;
use App\Livewire\BankManager;
use App\Livewire\MonthSummary;
use App\Livewire\Transfers;
use App\Models\Bank;
use App\Models\Transfer;
use App\Models\User;
use App\Services\Ledger\BankBalanceService;
use App\Services\Ledger\MonthBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransfersTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_offers_transfers_between_banks(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertSee('Pasar entre bancos')
            ->assertSeeLivewire(Transfers::class);
    }

    public function test_guest_cannot_open_transfers(): void
    {
        Livewire::test(Transfers::class)->assertForbidden();
    }

    public function test_transfer_moves_the_balance_and_does_not_count_as_income_or_expense(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $from = $this->bank($user, 'Banesco', ['opening_usd' => 100]);
        $to = $this->bank($user, 'Zelle');
        $this->actingAs($user);

        app(RecordTransferAction::class)->execute($user, [
            'from_bank_id' => $from->id,
            'to_bank_id' => $to->id,
            'occurred_on' => '2026-10-08',
            'currency' => 'USD',
            'amount' => '15',
            'notes' => null,
        ]);

        $accounts = app(BankBalanceService::class)->forUser($user)->keyBy(fn (array $row): int => $row['bank']->id);
        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 10);

        $this->assertEqualsWithDelta(85.0, $accounts[$from->id]['balances']['USD'], 0.001);
        $this->assertEqualsWithDelta(15.0, $accounts[$to->id]['balances']['USD'], 0.001);
        $this->assertEqualsWithDelta(0.0, $summary['income_usd'], 0.001);
        $this->assertEqualsWithDelta(0.0, $summary['expense_usd'], 0.001);

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->assertSee('Banesco → Zelle');
    }

    public function test_deleting_a_transfer_puts_the_money_back(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $from = $this->bank($user, 'Banesco', ['opening_usd' => 100]);
        $to = $this->bank($user, 'Zelle');

        Livewire::actingAs($user)
            ->test(Transfers::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('to_bank_id', (string) $to->id)
            ->set('occurred_on', '2026-10-08')
            ->set('currency', 'USD')
            ->set('amount', '15')
            ->call('save')
            ->assertHasNoErrors()
            ->call('askRemoval', Transfer::query()->value('id'))
            ->call('delete', Transfer::query()->value('id'))
            ->assertHasNoErrors();

        $this->assertSame(0, Transfer::query()->count());
        $accounts = app(BankBalanceService::class)->forUser($user)->keyBy(fn (array $row): int => $row['bank']->id);
        $this->assertEqualsWithDelta(100.0, $accounts[$from->id]['balances']['USD'], 0.001);
        $this->assertEqualsWithDelta(0.0, $accounts[$to->id]['balances']['USD'], 0.001);
    }

    public function test_transfer_to_the_same_bank_is_rejected(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $bank = $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(Transfers::class)
            ->set('from_bank_id', (string) $bank->id)
            ->set('to_bank_id', (string) $bank->id)
            ->set('occurred_on', '2026-10-08')
            ->set('currency', 'USD')
            ->set('amount', '15')
            ->call('save')
            ->assertHasErrors(['to_bank_id' => 'El destino tiene que ser otro banco.']);

        $this->assertSame(0, Transfer::query()->count());
    }

    public function test_transfer_rejects_another_persons_bank(): void
    {
        $user = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $own = $this->bank($user, 'Banesco');
        $foreign = $this->bank($other, 'Mercantil');

        Livewire::actingAs($user)
            ->test(Transfers::class)
            ->set('from_bank_id', (string) $own->id)
            ->set('to_bank_id', (string) $foreign->id)
            ->set('occurred_on', '2026-10-08')
            ->set('currency', 'USD')
            ->set('amount', '15')
            ->call('save')
            ->assertHasErrors(['to_bank_id' => 'Ese banco no está en tu lista.']);

        $this->assertSame(0, Transfer::query()->count());
    }

    public function test_missing_rate_does_not_save_the_transfer(): void
    {
        $user = $this->usuario();
        $from = $this->bank($user, 'Banesco');
        $to = $this->bank($user, 'Zelle');

        Livewire::actingAs($user)
            ->test(Transfers::class)
            ->set('from_bank_id', (string) $from->id)
            ->set('to_bank_id', (string) $to->id)
            ->set('occurred_on', '2026-10-08')
            ->set('currency', 'USD')
            ->set('amount', '15')
            ->call('save')
            ->assertHasErrors(['amount' => 'No hay tasa BCV en dólares para esa fecha.']);

        $this->assertSame(0, Transfer::query()->count());
    }

    public function test_bank_used_in_a_transfer_cannot_be_deleted(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $from = $this->bank($user, 'Banesco');
        $to = $this->bank($user, 'Zelle');
        $this->actingAs($user);
        app(RecordTransferAction::class)->execute($user, [
            'from_bank_id' => $from->id,
            'to_bank_id' => $to->id,
            'occurred_on' => '2026-10-08',
            'currency' => 'USD',
            'amount' => '15',
            'notes' => null,
        ]);

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->call('askRemoval', $from->id)
            ->call('delete', $from->id)
            ->assertHasErrors(['removal' => 'Este banco tiene movimientos. Puedes cambiarle el nombre.']);

        $this->assertModelExists($from);
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
}
