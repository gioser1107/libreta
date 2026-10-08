<?php

namespace Tests\Feature;

use App\Actions\Ledger\DeleteExpenseBudgetAction;
use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\SaveExpenseBudgetAction;
use App\Livewire\MonthSummary;
use App\Models\ExpenseBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExpenseBudgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_shows_when_a_category_passes_its_cap(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-10-08', 100, 110);
        $this->actingAs($user);
        app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-10-08',
            'concept' => 'Mercado',
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '100',
            'payment_method' => 'efectivo',
            'status' => 'paid',
            'notes' => null,
        ]);

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->set('budgetCategory', 'comida')
            ->set('budgetAmount', '80')
            ->call('saveBudget')
            ->assertHasNoErrors()
            ->assertSee('$ 100,00 de $ 80,00')
            ->assertSee('pasado')
            ->call('clearBudget', 'comida')
            ->assertDontSee('pasado');

        $this->assertSame(0, ExpenseBudget::query()->count());
    }

    public function test_a_cap_of_zero_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->set('budgetCategory', 'comida')
            ->set('budgetAmount', '0')
            ->call('saveBudget')
            ->assertHasErrors(['limit_usd' => 'El tope debe ser mayor a 0.']);

        $this->assertSame(0, ExpenseBudget::query()->count());
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
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
        app(SaveExpenseBudgetAction::class)->execute($owner, 'comida', '40');
        $this->actingAs($other);

        app(DeleteExpenseBudgetAction::class)->execute($other, 'comida');

        $this->assertEqualsWithDelta(40.0, (float) ExpenseBudget::query()->where('user_id', $owner->id)->value('limit_usd'), 0.001);
    }

    public function test_another_person_cannot_save_a_cap_for_the_owner(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->actingAs($other);

        try {
            app(SaveExpenseBudgetAction::class)->execute($owner, 'comida', '40');
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
}
