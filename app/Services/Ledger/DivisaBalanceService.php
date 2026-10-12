<?php

namespace App\Services\Ledger;

use App\Models\CurrencyExchange;
use App\Models\DivisaPlace;
use App\Models\Income;
use App\Models\User;

class DivisaBalanceService
{
    /**
     * @return list<array{id: int, label: string, balances: array{USD: float, EUR: float}}>
     */
    public function forUser(User $user): array
    {
        $places = DivisaPlace::query()->ownedBy($user)->orderBy('name')->orderBy('id')->get();
        $balances = [];

        foreach ($places as $place) {
            $balances[$place->id] = [
                Income::CURRENCY_USD => 0.0,
                Income::CURRENCY_EUR => 0.0,
            ];
        }

        $rows = CurrencyExchange::query()
            ->ownedBy($user)
            ->get(['divisa_place_id', 'paid_currency', 'paid_amount', 'received_currency', 'received_amount']);

        foreach ($rows as $row) {
            $placeId = $row->divisa_place_id;
            if (! isset($balances[$placeId])) {
                continue;
            }

            if ($row->received_currency !== Income::CURRENCY_VES && isset($balances[$placeId][$row->received_currency])) {
                $balances[$placeId][$row->received_currency] = round(
                    $balances[$placeId][$row->received_currency] + (float) $row->received_amount,
                    2,
                );
            }

            if ($row->paid_currency !== Income::CURRENCY_VES && isset($balances[$placeId][$row->paid_currency])) {
                $balances[$placeId][$row->paid_currency] = round(
                    $balances[$placeId][$row->paid_currency] - (float) $row->paid_amount,
                    2,
                );
            }
        }

        return $places->map(fn (DivisaPlace $place): array => [
            'id' => $place->id,
            'label' => $place->name,
            'balances' => $balances[$place->id],
        ])->all();
    }
}
