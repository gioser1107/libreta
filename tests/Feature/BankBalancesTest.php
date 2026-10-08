<?php

namespace Tests\Feature;

use App\Actions\Banks\SetBankOpeningAction;
use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\RecordIncomeAction;
use App\Livewire\BankManager;
use App\Models\Bank;
use App\Models\User;
use App\Services\Ledger\BankBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class BankBalancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_adds_opening_and_paid_movements_in_each_currency(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        $this->bcv('2026-10-08', 100, 110);
        $bank = $this->bank($user, 'Banesco', ['opening_usd' => 40, 'opening_eur' => 3]);

        app(RecordIncomeAction::class)->execute($user, $this->money($bank, 'ingreso', 10));
        app(RecordExpenseAction::class)->execute($user, [
            ...$this->money($bank, 'mercado', 4),
            'category' => 'comida',
            'payment_method' => 'zelle',
            'status' => 'paid',
        ]);
        app(RecordExpenseAction::class)->execute($user, [
            ...$this->money($bank, 'pendiente', 3),
            'category' => 'comida',
            'payment_method' => 'zelle',
            'status' => 'pending',
        ]);

        $account = app(BankBalanceService::class)->forUser($user)->first();

        $this->assertNotNull($account);
        $this->assertEqualsWithDelta(46.0, $account['balances']['USD'], 0.001);
        $this->assertEqualsWithDelta(3.0, $account['balances']['EUR'], 0.001);
        $this->assertEqualsWithDelta(0.0, $account['balances']['VES'], 0.001);
        $this->assertSame('$ 46,00 · € 3,00', $account['label']);
    }

    public function test_cash_without_a_bank_does_not_change_the_bank_balance(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        $this->bcv('2026-10-08', 100, 110);
        $bank = $this->bank($user, 'Efectivo', ['opening_usd' => 20]);

        app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-10-08',
            'concept' => 'Café',
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '5',
            'payment_method' => null,
            'bank_id' => null,
            'status' => 'paid',
            'notes' => null,
        ]);

        $account = app(BankBalanceService::class)->forUser($user)->firstWhere(fn (array $row): bool => $row['bank']->is($bank));

        $this->assertEqualsWithDelta(20.0, $account['balances']['USD'], 0.001);
    }

    public function test_another_persons_movement_does_not_change_the_balance(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $bank = $this->bank($owner, 'Banesco', ['opening_usd' => 10]);
        $foreign = $this->bank($other, 'Mercantil', ['opening_usd' => 99]);

        $this->actingAs($other);
        app(RecordIncomeAction::class)->execute($other, $this->money($foreign, 'Ajeno', 50));

        $this->actingAs($owner);
        $account = app(BankBalanceService::class)->forUser($owner)->first();

        $this->assertTrue($account['bank']->is($bank));
        $this->assertEqualsWithDelta(10.0, $account['balances']['USD'], 0.001);
    }

    public function test_user_saves_an_opening_balance_and_sees_it(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', 'Banesco')
            ->set('openingUsd', '40.50')
            ->set('openingEur', '3')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Banesco')
            ->assertSee('$ 40,50 · € 3,00');

        $bank = Bank::query()->first();
        $this->assertNotNull($bank);
        $this->assertEqualsWithDelta(40.5, (float) $bank->opening_usd, 0.001);
        $this->assertEqualsWithDelta(3.0, (float) $bank->opening_eur, 0.001);
    }

    public function test_a_balance_that_is_not_a_number_is_rejected_and_the_bank_is_not_created(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', 'Banesco')
            ->set('openingUsd', 'no')
            ->call('save')
            ->assertHasErrors(['opening_usd' => 'El saldo en dólares tiene que ser un número.']);

        $this->assertSame(0, Bank::query()->count());
    }

    public function test_another_person_cannot_set_an_opening_balance(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $bank = $this->bank($owner, 'Banesco');
        $this->actingAs($other);

        try {
            app(SetBankOpeningAction::class)->execute($owner, $bank->id, [
                'opening_usd' => '80',
            ]);
            $this->fail('A foreign user was able to set an opening balance.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->expectException(NotFoundHttpException::class);
        app(SetBankOpeningAction::class)->execute($other, $bank->id, [
            'opening_usd' => '80',
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function money(Bank $bank, string $concept, float $amount): array
    {
        return [
            'occurred_on' => '2026-10-08',
            'concept' => $concept,
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => (string) $amount,
            'bank_id' => $bank->id,
            'notes' => null,
        ];
    }
}
