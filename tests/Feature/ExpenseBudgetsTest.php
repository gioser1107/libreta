<?php

namespace Tests\Feature;

use App\Actions\Ledger\DeleteExpenseBudgetAction;
use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\SaveExpenseBudgetAction;
use App\Livewire\ExpenseBudgets;
use App\Livewire\MonthSummary;
use App\Models\ExpenseBudget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExpenseBudgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_passed_cap_shows_on_its_page_and_not_on_the_summary(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $this->actingAs($user);
        app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-10-08',
            'concept' => 'Mercado',
            'category' => 'alimentacion',
            'currency' => 'USD',
            'amount' => '100',
            'payment_method' => 'efectivo',
            'status' => 'paid',
            'notes' => null,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-10-12 09:00:00', 'America/Caracas'));

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->set('budgetCategory', 'alimentacion')
            ->set('budgetAmount', '80')
            ->call('saveBudget')
            ->assertHasNoErrors()
            ->assertSee('$ 100,00 de $ 80,00')
            ->assertSee('pasado');

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->assertSee('Alimentación')
            ->assertDontSee('Poner tope')
            ->assertDontSee('$ 100,00 de $ 80,00')
            ->assertDontSee('pasado');

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->call('clearBudget', 'alimentacion')
            ->assertDontSee('pasado');

        Carbon::setTestNow();

        $this->assertSame(0, ExpenseBudget::query()->count());
    }

    public function test_a_cap_of_zero_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->set('budgetCategory', 'alimentacion')
            ->set('budgetAmount', '0')
            ->call('saveBudget')
            ->assertHasErrors(['limit_usd' => 'El tope debe ser mayor a 0.']);

        $this->assertSame(0, ExpenseBudget::query()->count());
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->set('budgetCategory', 'nope')
            ->set('budgetAmount', '80')
            ->call('saveBudget')
            ->assertHasErrors(['category' => 'Esa categoría no existe.']);

        $this->assertSame(0, ExpenseBudget::query()->count());
    }

    public function test_clearing_a_cap_does_not_remove_another_persons_cap(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->actingAs($owner);
        app(SaveExpenseBudgetAction::class)->execute($owner, 'alimentacion', '40');
        $this->actingAs($other);

        app(DeleteExpenseBudgetAction::class)->execute($other, 'alimentacion');

        $this->assertEqualsWithDelta(40.0, (float) ExpenseBudget::query()->where('user_id', $owner->id)->value('limit_usd'), 0.001);
    }

    public function test_another_person_cannot_save_a_cap_for_the_owner(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->actingAs($other);

        try {
            app(SaveExpenseBudgetAction::class)->execute($owner, 'alimentacion', '40');
            $this->fail('A foreign user was able to save a cap.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, ExpenseBudget::query()->count());
    }

    public function test_a_user_without_permission_cannot_open_the_summary(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(MonthSummary::class)
            ->assertForbidden();
    }

    public function test_caps_live_on_their_own_account_page(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Topes');

        $this->actingAs($user)
            ->get(route('profile', ['seccion' => 'topes']))
            ->assertOk()
            ->assertSee('Todavía no hay topes.')
            ->assertSee('Nuevo')
            ->assertDontSee('Nuevo tope')
            ->assertDontSee('En qué se fue');
    }

    public function test_the_cap_form_opens_in_a_modal(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        app(SaveExpenseBudgetAction::class)->execute($user, 'alimentacion', '80');

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->assertDontSee('Nuevo tope')
            ->call('openModal')
            ->assertSee('Nuevo tope')
            ->assertSee('Tope en dólares')
            ->call('closeModal')
            ->call('edit', 'alimentacion')
            ->assertSee('Editar tope')
            ->assertSet('budgetAmount', '80');
    }

    public function test_a_rejected_move_leaves_the_cap_where_it_was(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        app(SaveExpenseBudgetAction::class)->execute($user, 'alimentacion', '80');

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->call('edit', 'alimentacion')
            ->set('budgetCategory', 'entretenimiento')
            ->set('budgetAmount', '0')
            ->call('saveBudget')
            ->assertHasErrors(['limit_usd' => 'El tope debe ser mayor a 0.']);

        $this->assertEqualsWithDelta(80.0, (float) ExpenseBudget::query()->where('category', 'alimentacion')->value('limit_usd'), 0.001);
        $this->assertSame(0, ExpenseBudget::query()->where('category', 'entretenimiento')->count());
    }

    public function test_moving_a_cap_keeps_a_single_category(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        app(SaveExpenseBudgetAction::class)->execute($user, 'alimentacion', '80');

        Livewire::actingAs($user)
            ->test(ExpenseBudgets::class)
            ->call('edit', 'alimentacion')
            ->set('budgetCategory', 'entretenimiento')
            ->set('budgetAmount', '90')
            ->call('saveBudget')
            ->assertHasNoErrors()
            ->assertSee('Entretenimiento y hobbies')
            ->assertDontSee('Alimentación');

        $this->assertSame(0, ExpenseBudget::query()->where('category', 'alimentacion')->count());
        $this->assertEqualsWithDelta(90.0, (float) ExpenseBudget::query()->where('category', 'entretenimiento')->value('limit_usd'), 0.001);
    }

    public function test_a_user_without_permission_cannot_open_the_caps(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ExpenseBudgets::class)
            ->assertForbidden();
    }
}
