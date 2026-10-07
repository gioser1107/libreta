<?php

namespace Tests\Unit;

use App\Models\BcvRate;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BcvRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_looks_up_the_passed_day_and_ignores_a_future_rate(): void
    {
        $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

        $thursday = BcvRate::query()->create([
            'currency_id' => $eur->id,
            'rate_to_ves' => 230,
            'date_effective' => '2026-08-20',
        ]);
        BcvRate::query()->create([
            'currency_id' => $eur->id,
            'rate_to_ves' => 250,
            'date_effective' => '2026-08-21',
        ]);

        $found = BcvRate::forCodeOnOrBefore('EUR', '2026-08-20');

        $this->assertNotNull($found);
        $this->assertSame($thursday->id, $found->id);
        $this->assertEqualsWithDelta(230.0, (float) $found->rate_to_ves, 0.001);
    }

    public function test_falls_back_to_the_last_rate_on_or_before_the_passed_day(): void
    {
        $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

        $wednesday = BcvRate::query()->create([
            'currency_id' => $eur->id,
            'rate_to_ves' => 220,
            'date_effective' => '2026-08-19',
        ]);
        BcvRate::query()->create([
            'currency_id' => $eur->id,
            'rate_to_ves' => 250,
            'date_effective' => '2026-08-21',
        ]);

        $found = BcvRate::forCodeOnOrBefore('EUR', '2026-08-20');

        $this->assertNotNull($found);
        $this->assertSame($wednesday->id, $found->id);
    }

    public function test_for_code_on_date_does_not_fall_back_to_a_previous_day(): void
    {
        $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

        BcvRate::query()->create([
            'currency_id' => $eur->id,
            'rate_to_ves' => 220,
            'date_effective' => '2026-08-19',
        ]);

        $this->assertNull(BcvRate::forCodeOnDate('EUR', '2026-08-20'));
    }

    public function test_for_money_rounds_the_official_rate_to_two_decimals(): void
    {
        $this->assertEqualsWithDelta(929.09, BcvRate::forMoney(929.09083243), 0.0001);
        $this->assertEqualsWithDelta(929.09, BcvRate::forMoney('929.09083243'), 0.0001);
        $this->assertEqualsWithDelta(929.10, BcvRate::forMoney(929.098), 0.0001);
        $this->assertEqualsWithDelta(929.10, BcvRate::forMoney(929.095), 0.0001);
        $this->assertEqualsWithDelta(885.08, BcvRate::forMoney(885.079484), 0.0001);
        $this->assertEqualsWithDelta(916.01, BcvRate::forMoney(916.008), 0.0001);
        $this->assertEqualsWithDelta(916.00, BcvRate::forMoney(916.001), 0.0001);
        $this->assertEqualsWithDelta(0.0, BcvRate::forMoney(null), 0.0001);
        $this->assertEqualsWithDelta(0.0, BcvRate::forMoney(0), 0.0001);
    }
}
