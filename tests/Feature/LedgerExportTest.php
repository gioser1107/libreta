<?php

namespace Tests\Feature;

use App\Actions\Ledger\RecordExpenseAction;
use App\Actions\Ledger\RecordIncomeAction;
use App\Actions\Ledger\RecordTransferAction;
use App\Livewire\IncomeLedger;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LedgerExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('ledger.export', ['month' => 10, 'year' => 2026]))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_permission_cannot_export(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('ledger.export', ['month' => 10, 'year' => 2026]))
            ->assertForbidden();
    }

    public function test_export_contains_the_month_and_leaves_out_other_people_and_months(): void
    {
        $user = $this->usuario();
        $other = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $this->bcv('2026-10-08', 100, 110);
        $from = Bank::query()->create(['user_id' => $user->id, 'name' => 'Banesco']);
        $to = Bank::query()->create(['user_id' => $user->id, 'name' => 'Zelle']);
        $this->actingAs($user);

        app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-10-08',
            'concept' => 'Sueldo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '10',
            'bank_id' => $from->id,
            'notes' => null,
        ]);
        app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Sueldo viejo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '5',
            'bank_id' => null,
            'notes' => null,
        ]);
        app(RecordExpenseAction::class)->execute($user, [
            'occurred_on' => '2026-10-08',
            'concept' => '=CMD()',
            'category' => 'comida',
            'currency' => 'USD',
            'amount' => '4',
            'payment_method' => 'efectivo',
            'status' => 'paid',
            'notes' => null,
        ]);
        app(RecordTransferAction::class)->execute($user, [
            'from_bank_id' => $from->id,
            'to_bank_id' => $to->id,
            'occurred_on' => '2026-10-08',
            'currency' => 'USD',
            'amount' => '2',
            'notes' => null,
        ]);

        $this->actingAs($other);
        app(RecordIncomeAction::class)->execute($other, [
            'occurred_on' => '2026-10-08',
            'concept' => 'Ajeno',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '9',
            'bank_id' => null,
            'notes' => null,
        ]);

        $content = $this->actingAs($user)
            ->get(route('ledger.export', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Sueldo', $content);
        $this->assertStringContainsString('Traspaso', $content);
        $this->assertStringContainsString('Banesco → Zelle', $content);
        $this->assertStringContainsString("'=CMD()", $content);
        $this->assertStringNotContainsString('Sueldo viejo', $content);
        $this->assertStringNotContainsString('Ajeno', $content);
    }

    public function test_an_invalid_month_is_rejected(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->get(route('ledger.export', ['month' => 13, 'year' => 2026]))
            ->assertSessionHasErrors('month');
    }

    public function test_search_finds_a_movement_from_another_month(): void
    {
        $user = $this->usuario();
        $this->bcv('2026-08-20', 100, 110);
        $this->actingAs($user);
        app(RecordIncomeAction::class)->execute($user, [
            'occurred_on' => '2026-08-20',
            'concept' => 'Sueldo viejo',
            'category' => 'sueldo',
            'currency' => 'USD',
            'amount' => '5',
            'bank_id' => null,
            'notes' => null,
        ]);

        Livewire::actingAs($user)
            ->test(IncomeLedger::class)
            ->set('month', 10)
            ->set('year', 2026)
            ->assertDontSee('Sueldo viejo')
            ->set('search', 'Sueldo viejo')
            ->assertSee('Sueldo viejo');
    }
}
