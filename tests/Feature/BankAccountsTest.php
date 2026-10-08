<?php

namespace Tests\Feature;

use App\Actions\Banks\CreateBankAction;
use App\Actions\Banks\RenameBankAction;
use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\RecordIncomeAction;
use App\Livewire\BankManager;
use App\Livewire\ExpenseLedger;
use App\Livewire\IncomeLedger;
use App\Livewire\MonthSummary;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class BankAccountsTest extends TestCase
{
    use RefreshDatabase;

    private const DANGEROUS_NAME = "<script>alert('xss')</script>";

    public function test_account_page_offers_bank_management(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->get(route('profile', ['seccion' => 'bancos']))
            ->assertSee('Mis bancos')
            ->assertSeeLivewire(BankManager::class);
    }

    public function test_guest_cannot_open_the_bank_manager(): void
    {
        Livewire::test(BankManager::class)->assertForbidden();
    }

    public function test_user_adds_a_bank_and_extra_spaces_are_collapsed(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', '  Banco  de  Venezuela  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Banco de Venezuela');

        $bank = Bank::query()->first();
        $this->assertNotNull($bank);
        $this->assertSame($user->id, $bank->user_id);
        $this->assertSame('Banco de Venezuela', $bank->name);
    }

    public function test_blank_bank_name_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', '   ')
            ->call('save')
            ->assertHasErrors(['name' => 'Escribe el nombre del banco.']);

        $this->assertSame(0, Bank::query()->count());
    }

    public function test_bank_name_longer_than_60_characters_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', str_repeat('a', 61))
            ->call('save')
            ->assertHasErrors(['name' => 'El nombre acepta hasta 60 caracteres.']);

        $this->assertSame(0, Bank::query()->count());
    }

    public function test_duplicate_bank_name_is_rejected_ignoring_case(): void
    {
        $user = $this->usuario();
        $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', 'banesco')
            ->call('save')
            ->assertHasErrors(['name' => 'Ya tienes un banco con ese nombre.']);

        $this->assertSame(1, Bank::query()->count());
    }

    public function test_user_renames_a_bank(): void
    {
        $user = $this->usuario();
        $bank = $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->call('edit', $bank->id)
            ->set('name', 'Mercantil')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('>Mercantil<', false)
            ->assertDontSee('>Banesco<', false);

        $this->assertSame('Mercantil', $bank->fresh()->name);
    }

    public function test_user_deletes_an_unused_bank(): void
    {
        $user = $this->usuario();
        $bank = $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->call('askRemoval', $bank->id)
            ->assertSee('¿Borrar Banesco?')
            ->call('delete', $bank->id)
            ->assertHasNoErrors()
            ->assertSee('Todavía no tienes bancos.')
            ->assertDontSee('>Banesco<', false);

        $this->assertModelMissing($bank);
    }

    public function test_cancelling_removal_keeps_the_bank(): void
    {
        $user = $this->usuario();
        $bank = $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->call('askRemoval', $bank->id)
            ->call('cancelRemoval')
            ->assertSee('Banesco');

        $this->assertModelExists($bank);
    }

    public function test_bank_with_a_movement_cannot_be_deleted(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, 'Banesco');
        $this->income($user, $bank);

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->call('askRemoval', $bank->id)
            ->call('delete', $bank->id)
            ->assertHasErrors(['removal' => 'Este banco tiene movimientos. Puedes cambiarle el nombre.'])
            ->assertSee('Banesco');

        $this->assertModelExists($bank);
    }

    public function test_another_person_cannot_rename_or_see_a_bank(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $bank = $this->bank($owner, 'Banesco');

        Livewire::actingAs($other)
            ->test(BankManager::class)
            ->assertDontSee('>Banesco<', false)
            ->assertSee('Todavía no tienes bancos.');

        $this->actingAs($other);
        $this->expectException(NotFoundHttpException::class);
        app(RenameBankAction::class)->execute($other, $bank->id, 'Mercantil');
    }

    public function test_another_person_cannot_create_a_bank_for_the_owner(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->actingAs($other);

        try {
            app(CreateBankAction::class)->execute($owner, 'Banesco');
            $this->fail('A foreign user was able to create a bank.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, Bank::query()->count());
    }

    public function test_bank_name_is_escaped_in_the_manager(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(BankManager::class)
            ->set('name', self::DANGEROUS_NAME)
            ->call('save')
            ->assertSee(self::DANGEROUS_NAME)
            ->assertDontSee(self::DANGEROUS_NAME, false);
    }

    public function test_income_stores_the_bank_and_shows_it(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Sueldo')
            ->set('category', 'sueldo')
            ->set('currency', 'USD')
            ->set('amount', '10')
            ->set('bank_id', (string) $bank->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Banesco')
            ->call('edit', Income::query()->value('id'))
            ->assertSet('bank_id', (string) $bank->id);

        $this->assertSame($bank->id, Income::query()->value('bank_id'));
    }

    public function test_income_rejects_a_bank_that_is_not_in_the_list(): void
    {
        $user = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $foreign = $this->bank($other, 'Mercantil');

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->call('openModal')
            ->assertDontSee('Mercantil')
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Sueldo')
            ->set('category', 'sueldo')
            ->set('currency', 'USD')
            ->set('amount', '10')
            ->set('bank_id', (string) $foreign->id)
            ->call('save')
            ->assertHasErrors(['bank_id' => 'Ese banco no está en tu lista.']);

        $this->assertSame(0, Income::query()->count());
    }

    public function test_income_rejects_a_bank_id_that_is_not_a_number(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Sueldo')
            ->set('category', 'sueldo')
            ->set('currency', 'USD')
            ->set('amount', '10')
            ->set('bank_id', 'no')
            ->call('save')
            ->assertHasErrors(['bank_id' => 'Ese banco no está en tu lista.']);

        $this->assertSame(0, Income::query()->count());
    }

    public function test_income_list_escapes_the_bank_name(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, self::DANGEROUS_NAME);
        $this->income($user, $bank);

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->assertSee(self::DANGEROUS_NAME)
            ->assertDontSee(self::DANGEROUS_NAME, false);
    }

    public function test_expense_form_asks_for_the_bank_after_a_payment_method(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->call('openModal')
            ->assertDontSee('Banco de la tarjeta')
            ->assertDontSee('Banco del efectivo')
            ->set('payment_method', 'tarjeta')
            ->assertSee('Banco de la tarjeta')
            ->set('payment_method', 'efectivo')
            ->assertSee('Banco del efectivo')
            ->assertDontSee('Banco de la tarjeta')
            ->set('payment_method', 'zelle')
            ->assertSee('Banco')
            ->assertDontSee('Banco del efectivo');
    }

    #[DataProvider('paymentMethodsThatUseABank')]
    public function test_expense_stores_the_bank_for_a_payment_method(string $method): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, 'Banesco');

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Mercado')
            ->set('category', 'comida')
            ->set('currency', 'USD')
            ->set('amount', '4')
            ->set('payment_method', $method)
            ->set('bank_id', (string) $bank->id)
            ->set('status', 'paid')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Banesco');

        $expense = Expense::query()->first();
        $this->assertNotNull($expense);
        $this->assertSame($method, $expense->payment_method);
        $this->assertSame($bank->id, $expense->bank_id);
    }

    public function test_expense_without_a_payment_method_drops_the_bank(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, 'Banesco');
        $this->actingAs($user);

        $expense = app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Mercado',
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '4',
            'payment_method' => null,
            'bank_id' => $bank->id,
            'status' => 'paid',
            'notes' => null,
        ]);

        $this->assertNull($expense->bank_id);
    }

    public function test_clearing_the_payment_method_clears_the_bank(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, 'Banesco');
        $expense = $this->expense($user, $bank, 'tarjeta');

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->call('edit', $expense->id)
            ->assertSet('bank_id', (string) $bank->id)
            ->set('payment_method', '')
            ->assertSet('bank_id', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($expense->fresh()->bank_id);
    }

    public function test_expense_list_escapes_the_bank_name(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, self::DANGEROUS_NAME);
        $this->expense($user, $bank, 'efectivo');

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->assertSee(self::DANGEROUS_NAME)
            ->assertDontSee(self::DANGEROUS_NAME, false);
    }

    public function test_month_summary_escapes_the_bank_name(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $bank = $this->bank($user, self::DANGEROUS_NAME);
        $this->income($user, $bank);

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->assertSee(self::DANGEROUS_NAME)
            ->assertDontSee(self::DANGEROUS_NAME, false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function paymentMethodsThatUseABank(): array
    {
        return [
            'efectivo' => ['efectivo'],
            'tarjeta' => ['tarjeta'],
            'transferencia' => ['transferencia'],
            'pago móvil' => ['pago_movil'],
            'punto' => ['punto'],
            'zelle' => ['zelle'],
        ];
    }

    private function bank(User $user, string $name): Bank
    {
        $this->actingAs($user);

        return app(CreateBankAction::class)->execute($user, $name);
    }

    private function income(User $user, Bank $bank): Income
    {
        $this->actingAs($user);

        return app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Sueldo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '10',
            'bank_id' => $bank->id,
            'notes' => null,
        ]);
    }

    private function expense(User $user, Bank $bank, string $method): Expense
    {
        $this->actingAs($user);

        return app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Mercado',
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '4',
            'payment_method' => $method,
            'bank_id' => $bank->id,
            'status' => 'paid',
            'notes' => null,
        ]);
    }
}
