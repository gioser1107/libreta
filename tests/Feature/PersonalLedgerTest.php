<?php

namespace Tests\Feature;

use App\Actions\Ledger\DeleteExpenseAction;
use App\Actions\Ledger\DeleteIncomeAction;
use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\RecordIncomeAction;
use App\Actions\Ledger\UpdateIncomeAction;
use App\Exceptions\LedgerException;
use App\Livewire\ExpenseLedger;
use App\Livewire\IncomeLedger;
use App\Livewire\MonthSummary;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use App\Services\Ledger\MonthBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class PersonalLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_assigns_the_usuario_role(): void
    {
        Livewire::test('pages.auth.register')
            ->set('name', 'Ana')
            ->set('email', 'ana@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'ana@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('usuario'));
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_a_user_without_permission_cannot_open_the_ledger(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('incomes.index'))->assertForbidden();
        $this->actingAs($stranger)->get(route('expenses.index'))->assertForbidden();
    }

    public function test_new_income_and_expense_are_saved_in_bolivares(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->assertSet('currency', 'VES')
            ->set('month', 8)
            ->set('year', 2026)
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Pago en bolívares')
            ->set('category', 'sueldo')
            ->set('amount', '100')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('currency', 'VES')
            ->assertSee('Total en dólares')
            ->assertSee('Total en bolívares')
            ->assertSee('Movimientos');

        $income = Income::query()->first();
        $this->assertNotNull($income);
        $this->assertSame('VES', $income->currency);

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->assertSet('currency', 'VES')
            ->set('month', 8)
            ->set('year', 2026)
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Mercado')
            ->set('category', 'comida')
            ->set('amount', '50')
            ->set('status', 'paid')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('currency', 'VES')
            ->assertSee('Total en dólares')
            ->assertSee('Por pagar')
            ->assertSee('Todos los estados');

        $expense = Expense::query()->first();
        $this->assertNotNull($expense);
        $this->assertSame('VES', $expense->currency);
    }

    public function test_income_uses_that_days_rate_and_ignores_tomorrow(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $this->bcv('2026-08-21', 250, 260);

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->set('occurred_on', '2026-08-20')
            ->set('concept', 'Sueldo de agosto')
            ->set('category', 'sueldo')
            ->set('currency', 'USD')
            ->set('amount', '10')
            ->call('save')
            ->assertHasNoErrors();

        $income = Income::query()->first();
        $this->assertNotNull($income);
        $this->assertSame($user->id, $income->user_id);
        $this->assertEqualsWithDelta(1000.0, (float) $income->amount_ves, 0.001);
        $this->assertEqualsWithDelta(10.0, (float) $income->amount_usd, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $income->usd_rate_to_ves, 0.001);
    }

    public function test_eur_amount_uses_the_money_rate_rounding(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        $this->bcv('2026-08-20', 50.005, 100.004);

        $expense = app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Mercado',
            'category' => 'comida',
            'currency' => 'EUR',
            'amount' => '10',
            'payment_method' => 'efectivo',
            'status' => 'paid',
            'notes' => null,
        ]);

        $this->assertEqualsWithDelta(1000.0, (float) $expense->amount_ves, 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $expense->amount_usd, 0.001);
    }

    public function test_missing_rate_does_not_save_the_movement(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->set('occurred_on', '2026-01-01')
            ->set('concept', 'Taxi')
            ->set('category', 'transporte')
            ->set('currency', 'USD')
            ->set('amount', '5')
            ->set('status', 'paid')
            ->call('save')
            ->assertHasErrors(['amount']);

        $this->assertSame(0, Expense::query()->count());
    }

    public function test_another_person_cannot_see_or_edit_the_movement(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $this->actingAs($owner);

        $income = app(RecordIncomeAction::class)->execute($owner, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Sueldo secreto',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '10',
            'notes' => null,
        ]);

        Livewire::actingAs($other)
            ->test(IncomeLedger::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->assertDontSee('Sueldo secreto');

        $this->actingAs($other);
        $this->expectException(NotFoundHttpException::class);
        app(UpdateIncomeAction::class)->execute($other, $income->id, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Cambiado',
            'category' => 'otro',
            'currency' => 'USD',
            'amount' => '1',
            'notes' => null,
        ]);
    }

    public function test_month_summary_subtracts_expenses_from_income(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        $this->bcv('2026-08-20', 100, 110);

        $payload = [
            'occurred_on' => '2026-08-20',
            'currency' => 'USD',
            'notes' => null,
        ];

        app(RecordIncomeAction::class)->execute($user, [
            ...$payload,
            'concept' => 'Sueldo',
            'category' => 'sueldo',
            'amount' => '10',
        ]);
        app(RecordExpenseAction::class)->execute($user, [
            ...$payload,
            'concept' => 'Comida',
            'category' => 'comida',
            'amount' => '4',
            'payment_method' => 'zelle',
            'status' => 'paid',
        ]);
        app(RecordExpenseAction::class)->execute($user, [
            ...$payload,
            'concept' => 'Luz',
            'category' => 'servicios',
            'amount' => '1',
            'payment_method' => null,
            'status' => 'pending',
        ]);

        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 8);

        $this->assertEqualsWithDelta(10.0, $summary['income_usd'], 0.001);
        $this->assertEqualsWithDelta(5.0, $summary['expense_usd'], 0.001);
        $this->assertEqualsWithDelta(1.0, $summary['expense_pending_usd'], 0.001);
        $this->assertEqualsWithDelta(5.0, $summary['balance_usd'], 0.001);
        $this->assertEqualsWithDelta(500.0, $summary['balance_ves'], 0.001);
        $this->assertEqualsWithDelta(0.0, $summary['opening_usd'], 0.001);
        $this->assertEqualsWithDelta(5.0, $summary['available_usd'], 0.001);

        $component = Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->assertSee('Último ingreso')
            ->assertSee('Último egreso')
            ->assertSee('Mayor gasto')
            ->assertSee('Mayor ingreso')
            ->assertSee('Sueldo')
            ->assertSee('Luz')
            ->assertSee('Comida')
            ->assertSee('Quedó el 50% de lo ingresado')
            ->assertSee('De dónde vino')
            ->assertSee('En qué se fue')
            ->assertSee('100%')
            ->assertSee('80%')
            ->assertSee('20%')
            ->assertSee('Últimos movimientos')
            ->assertDontSee('Sueldo secreto');

        $html = $component->html();
        $this->assertMatchesRegularExpression(
            '/Último egreso<\/span>[\s\S]*?<span class="lb-highlight-title">Luz<\/span>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/Mayor gasto<\/span>[\s\S]*?<span class="lb-highlight-title">Comida<\/span>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/Último ingreso<\/span>[\s\S]*?<span class="lb-highlight-title">Sueldo<\/span>/',
            $html,
        );
    }

    public function test_a_later_month_shows_the_unspent_balance_beside_that_months_total(): void
    {
        $user = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-09-10', 100, 110);

        $this->actingAs($user);
        app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-09-10',
            'concept' => 'Sueldo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '150',
            'notes' => null,
        ]);
        $this->actingAs($other);
        app(RecordIncomeAction::class)->execute($other, [
            'occurred_on' => '2026-09-10',
            'concept' => 'Ajeno',
            'category' => 'otro',
            'currency' => 'USD',
            'amount' => '500',
            'notes' => null,
        ]);

        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 10);

        $this->assertEqualsWithDelta(0.0, $summary['balance_usd'], 0.001);
        $this->assertEqualsWithDelta(150.0, $summary['opening_usd'], 0.001);
        $this->assertEqualsWithDelta(15000.0, $summary['opening_ves'], 0.001);
        $this->assertEqualsWithDelta(150.0, $summary['available_usd'], 0.001);

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->assertSee('Tienes')
            ->assertSee('$ 150,00')
            ->assertSee('Septiembre dejó')
            ->assertSee('$ 0,00')
            ->assertDontSee('Ajeno')
            ->assertDontSee('$ 500,00')
            ->assertDontSee('$ 650,00');
    }

    public function test_spending_in_the_later_month_reduces_what_the_previous_month_left(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        $this->bcv('2026-09-10', 100, 110);

        app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-09-10',
            'concept' => 'Sueldo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '150',
            'notes' => null,
        ]);
        app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-10-02',
            'concept' => 'Comida',
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '40',
            'payment_method' => 'zelle',
            'status' => 'paid',
            'notes' => null,
        ]);

        $summary = app(MonthBalanceService::class)->summarize($user, 2026, 10);

        $this->assertEqualsWithDelta(150.0, $summary['opening_usd'], 0.001);
        $this->assertEqualsWithDelta(-40.0, $summary['balance_usd'], 0.001);
        $this->assertEqualsWithDelta(110.0, $summary['available_usd'], 0.001);
    }

    public function test_month_summary_states_when_the_month_has_no_movements(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 8)
            ->set('year', 2026)
            ->assertSee('Sin ingresos')
            ->assertSee('Sin egresos')
            ->assertSee('No hay movimientos en agosto 2026.')
            ->assertDontSee('De dónde vino')
            ->assertDontSee('En qué se fue');
    }

    public function test_user_removes_an_income_from_the_month(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $income = $this->recordIncome($user, 'Sueldo de agosto');

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->call('edit', $income->id)
            ->assertSee('Borrar')
            ->call('askRemoval')
            ->assertSee('¿Borrar este ingreso?')
            ->call('delete', $income->id)
            ->assertSet('showModal', false)
            ->assertDontSee('Sueldo de agosto');

        $this->assertModelMissing($income);
    }

    public function test_user_removes_an_expense_from_the_month(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $expense = $this->recordExpense($user, 'Mercado');

        Livewire::actingAs($user)
            ->test(ExpenseLedger::class)
            ->call('edit', $expense->id)
            ->assertSee('Borrar')
            ->call('askRemoval')
            ->assertSee('¿Borrar este egreso?')
            ->call('delete', $expense->id)
            ->assertSet('showModal', false)
            ->assertDontSee('Mercado');

        $this->assertModelMissing($expense);
    }

    public function test_cancelling_removal_keeps_the_income(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $income = $this->recordIncome($user, 'Sueldo de agosto');

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->call('edit', $income->id)
            ->call('askRemoval')
            ->call('cancelRemoval')
            ->assertSet('confirmingRemoval', false)
            ->assertSee('Sueldo de agosto');

        $this->assertModelExists($income);
    }

    public function test_another_person_cannot_remove_an_income(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $income = $this->recordIncome($owner, 'Sueldo secreto');

        $this->actingAs($other);
        $this->expectException(NotFoundHttpException::class);
        app(DeleteIncomeAction::class)->execute($other, $income->id);
    }

    public function test_another_person_cannot_remove_an_expense(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $expense = $this->recordExpense($owner, 'Gasto secreto');

        $this->actingAs($other);
        $this->expectException(NotFoundHttpException::class);
        app(DeleteExpenseAction::class)->execute($other, $expense->id);
    }

    public function test_quote_refuses_a_movement_when_the_rate_is_missing(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        $this->expectException(LedgerException::class);
        app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-01-01',
            'concept' => 'Extra',
            'category' => 'otro',
            'currency' => 'VES',
            'amount' => '100',
            'notes' => null,
        ]);
    }

    private function recordIncome(User $user, string $concept): Income
    {
        $this->actingAs($user);

        return app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => $concept,
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '10',
            'notes' => null,
        ]);
    }

    private function recordExpense(User $user, string $concept): Expense
    {
        $this->actingAs($user);

        return app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => $concept,
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '4',
            'payment_method' => 'efectivo',
            'status' => 'paid',
            'notes' => null,
        ]);
    }
}
