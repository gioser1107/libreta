<?php

namespace App\Actions\Ledger;

use App\Models\CurrencyExchange;
use App\Models\User;
use App\Services\Ledger\MoneyQuoteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCurrencyExchangeAction
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(User $user, array $input): CurrencyExchange
    {
        abort_unless(auth()->id() === $user->id, 403);

        $input['notes'] = filled($input['notes'] ?? null) ? trim((string) $input['notes']) : null;
        $data = validator($input, CurrencyExchange::rules($user), CurrencyExchange::messages())->validate();

        if ($data['paid_currency'] === $data['received_currency']) {
            throw ValidationException::withMessages([
                'received_currency' => 'Elige otra moneda. Para la misma moneda usa un traspaso.',
            ]);
        }

        $bolivarSides = (int) ($data['paid_currency'] === 'VES') + (int) ($data['received_currency'] === 'VES');
        if ($bolivarSides !== 1) {
            throw ValidationException::withMessages([
                'received_currency' => 'El cambio es entre bolívares y divisas.',
            ]);
        }

        $paid = $this->quotes->quote($data['occurred_on'], $data['paid_currency'], $data['paid_amount']);
        $received = $this->quotes->quote($data['occurred_on'], $data['received_currency'], $data['received_amount']);
        $spreadUsd = round((float) $paid['amount_usd'] - (float) $received['amount_usd'], 2);
        $spreadVes = round((float) $paid['amount_ves'] - (float) $received['amount_ves'], 2);

        return DB::transaction(function () use ($user, $data, $paid, $received, $spreadUsd, $spreadVes) {
            return CurrencyExchange::query()->create([
                'user_id' => $user->id,
                'from_bank_id' => $data['from_bank_id'],
                'divisa_place_id' => $data['divisa_place_id'],
                'occurred_on' => $data['occurred_on'],
                'paid_currency' => $paid['currency'],
                'paid_amount' => $paid['amount'],
                'paid_amount_ves' => $paid['amount_ves'],
                'paid_amount_usd' => $paid['amount_usd'],
                'received_currency' => $received['currency'],
                'received_amount' => $received['amount'],
                'received_amount_ves' => $received['amount_ves'],
                'received_amount_usd' => $received['amount_usd'],
                'usd_rate_to_ves' => $paid['usd_rate_to_ves'],
                'usd_bcv_rate_id' => $paid['usd_bcv_rate_id'],
                'spread_usd' => $spreadUsd,
                'spread_ves' => $spreadVes,
                'notes' => $data['notes'],
            ]);
        });
    }
}
