<?php

namespace Tests\Feature;

use App\Livewire\MonthSummary;
use App\Livewire\RecurringEntries;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringEntry;
use App\Models\User;
use App\Services\Ledger\BankBalanceService;
use App\Services\Ledger\PostRecurringEntries;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecurringEntriesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_open_recurring_entries(): void
    {
        Livewire::test(RecurringEntries::class)->assertForbidden();
    }

    public function test_a_fixed_income_is_posted_once_on_its_day(): void
    {
        $user = $this->usuario();
        $this->on('2026-10-08');
        $this->bcv('2026-10-01', 100, 110);

        Livewire::actingAs($user)
            ->test(RecurringEntries::class)
            ->set('kind', 'income')
            ->set('concept', 'Sueldo')
            ->set('category', 'sueldo')
            ->set('currency', 'USD')
            ->set('amount', '10')
            ->set('day_of_month', '1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Sueldo');

        $this->assertSame(1, Income::query()->count());
        $income = Income::query()->first();
        $this->assertNotNull($income);
        $this->assertSame($user->id, $income->user_id);
        $this->assertSame('2026-10-01', $income->occurred_on->toDateString());
        $this->assertEqualsWithDelta(10.0, (float) $income->amount_usd, 0.001);

        app(PostRecurringEntries::class)->postFor($user);
        $this->assertSame(1, Income::query()->count());
    }

    public function test_a_fixed_entry_waits_until_its_day(): void
    {
        $user = $this->usuario();
        $this->on('2026-10-08');
        $this->bcv('2026-10-20', 100, 110);
        $this->entry($user, ['day_of_month' => 20]);

        $this->assertSame(0, app(PostRecurringEntries::class)->postFor($user));
        $this->assertSame(0, Income::query()->count());
    }

    public function test_day_31_is_posted_on_the_last_day_of_february(): void
    {
        $user = $this->usuario();
        $this->on('2026-02-28');
        $this->bcv('2026-02-28', 100, 110);
        $this->entry($user, ['day_of_month' => 31, 'concept' => 'Alquiler']);

        app(PostRecurringEntries::class)->postFor($user);

        $income = Income::query()->first();
        $this->assertNotNull($income);
        $this->assertSame('2026-02-28', $income->occurred_on->toDateString());
    }

    public function test_day_31_is_not_posted_before_the_end_of_february(): void
    {
        $user = $this->usuario();
        $this->on('2026-02-27');
        $this->bcv('2026-02-28', 100, 110);
        $this->entry($user, ['day_of_month' => 31]);

        app(PostRecurringEntries::class)->postFor($user);

        $this->assertSame(0, Income::query()->count());
    }

    public function test_deleting_the_posted_movement_does_not_create_it_again(): void
    {
        $user = $this->usuario();
        $this->on('2026-10-08');
        $this->bcv('2026-10-01', 100, 110);
        $this->entry($user, ['day_of_month' => 1]);
        $poster = app(PostRecurringEntries::class);
        $poster->postFor($user);

        Income::query()->delete();
        $poster->postFor($user);

        $this->assertSame(0, Income::query()->count());
    }

    public function test_an_inactive_entry_is_not_posted(): void
    {
        $user = $this->usuario();
        $this->on('2026-10-08');
        $this->bcv('2026-10-01', 100, 110);
        $this->entry($user, ['day_of_month' => 1, 'active' => false]);

        app(PostRecurringEntries::class)->postFor($user);

        $this->assertSame(0, Income::query()->count());
    }

    public function test_a_pending_fixed_expense_does_not_reduce_the_bank_balance(): void
    {
        $user = $this->usuario();
        $this->on('2026-10-08');
        $this->bcv('2026-10-01', 100, 110);
        $bank = Bank::query()->create([
            'user_id' => $user->id,
            'name' => 'Banesco',
            'opening_usd' => 50,
        ]);
        $this->entry($user, [
            'kind' => RecurringEntry::KIND_EXPENSE,
            'category' => 'vivienda',
            'concept' => 'Alquiler',
            'amount' => 20,
            'bank_id' => $bank->id,
            'status' => Expense::STATUS_PENDING,
            'day_of_month' => 1,
        ]);

        app(PostRecurringEntries::class)->postFor($user);

        $expense = Expense::query()->first();
        $this->assertNotNull($expense);
        $this->assertSame(Expense::STATUS_PENDING, $expense->status);
        $account = app(BankBalanceService::class)->forUser($user)->first();
        $this->assertEqualsWithDelta(50.0, $account['balances']['USD'], 0.001);
    }

    public function test_posting_for_one_user_does_not_post_another_users_entry(): void
    {
        $owner = $this->usuario();
        $other = $this->usuario();
        $this->on('2026-10-08');
        $this->bcv('2026-10-01', 100, 110);
        $this->entry($other, ['day_of_month' => 1, 'concept' => 'Ajeno']);

        app(PostRecurringEntries::class)->postFor($owner);

        $this->assertSame(0, Income::query()->count());

        $this->artisan('ledger:post-recurring')->assertSuccessful();

        $income = Income::query()->first();
        $this->assertNotNull($income);
        $this->assertSame($other->id, $income->user_id);
        $this->assertSame('Ajeno', $income->concept);
    }

    public function test_a_day_outside_the_month_is_rejected(): void
    {
        $user = $this->usuario();

        Livewire::actingAs($user)
            ->test(RecurringEntries::class)
            ->set('concept', 'Alquiler')
            ->set('amount', '20')
            ->set('day_of_month', '0')
            ->call('save')
            ->assertHasErrors(['day_of_month' => 'El día tiene que estar entre 1 y 31.']);

        $this->assertSame(0, RecurringEntry::query()->count());
    }

    public function test_missing_rate_does_not_block_the_summary(): void
    {
        $user = $this->usuario();
        $this->on('2026-10-08');
        $this->entry($user, ['day_of_month' => 1]);

        Livewire::actingAs($user)
            ->test(MonthSummary::class)
            ->assertOk();

        $this->assertSame(0, Income::query()->count());
    }

    private function on(string $date): void
    {
        Carbon::setTestNow(Carbon::parse($date.' 09:00:00', 'America/Caracas'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function entry(User $user, array $overrides = []): RecurringEntry
    {
        return RecurringEntry::query()->create([
            'user_id' => $user->id,
            'kind' => RecurringEntry::KIND_INCOME,
            'concept' => 'Sueldo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => 10,
            'bank_id' => null,
            'payment_method' => null,
            'status' => 'paid',
            'notes' => null,
            'day_of_month' => 1,
            'active' => true,
            ...$overrides,
        ]);
    }
}
