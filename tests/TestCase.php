<?php

namespace Tests;

use App\Models\BcvRate;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }
    }

    protected function usuario(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $user->assignRole('usuario');

        return $user;
    }

    protected function bcv(string $date, float $usd, float $eur): void
    {
        $usdId = Currency::query()->where('code', 'USD')->value('id');
        $eurId = Currency::query()->where('code', 'EUR')->value('id');

        BcvRate::query()->updateOrCreate(
            ['currency_id' => $usdId, 'date_effective' => $date],
            ['rate_to_ves' => $usd],
        );
        BcvRate::query()->updateOrCreate(
            ['currency_id' => $eurId, 'date_effective' => $date],
            ['rate_to_ves' => $eur],
        );
    }
}
