<?php

namespace App\Actions\Banks;

use App\Models\Bank;
use App\Models\User;

class SetBankOpeningAction
{
    /**
     * @param  array<string, mixed>  $amounts
     */
    public function execute(User $user, int $bankId, array $amounts): Bank
    {
        abort_unless(auth()->id() === $user->id, 403);

        $data = $this->validated($amounts);
        $bank = Bank::query()->ownedBy($user)->find($bankId);
        abort_if($bank === null, 404);
        $bank->update($data);

        return $bank;
    }

    /**
     * @param  array<string, mixed>  $amounts
     * @return array{opening_ves: string, opening_usd: string, opening_eur: string}
     */
    public function validated(array $amounts): array
    {
        $data = validator([
            'opening_ves' => $this->blankAsZero($amounts['opening_ves'] ?? null),
            'opening_usd' => $this->blankAsZero($amounts['opening_usd'] ?? null),
            'opening_eur' => $this->blankAsZero($amounts['opening_eur'] ?? null),
        ], [
            'opening_ves' => $this->amountRule(),
            'opening_usd' => $this->amountRule(),
            'opening_eur' => $this->amountRule(),
        ], [
            'opening_ves.numeric' => 'El saldo en bolívares tiene que ser un número.',
            'opening_usd.numeric' => 'El saldo en dólares tiene que ser un número.',
            'opening_eur.numeric' => 'El saldo en euros tiene que ser un número.',
            'opening_ves.decimal' => 'El saldo acepta hasta 2 decimales.',
            'opening_usd.decimal' => 'El saldo acepta hasta 2 decimales.',
            'opening_eur.decimal' => 'El saldo acepta hasta 2 decimales.',
            'opening_ves.between' => 'Ese saldo es demasiado grande.',
            'opening_usd.between' => 'Ese saldo es demasiado grande.',
            'opening_eur.between' => 'Ese saldo es demasiado grande.',
        ], [
            'opening_ves' => 'saldo en bolívares',
            'opening_usd' => 'saldo en dólares',
            'opening_eur' => 'saldo en euros',
        ])->validate();

        return $data;
    }

    private function blankAsZero(mixed $amount): string
    {
        if ($amount === null) {
            return '0';
        }

        $amount = trim((string) $amount);

        return $amount === '' ? '0' : $amount;
    }

    /**
     * @return list<string>
     */
    private function amountRule(): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'between:-999999999.99,999999999.99'];
    }
}
