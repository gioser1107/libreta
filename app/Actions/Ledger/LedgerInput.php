<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

class LedgerInput
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function income(User $user, array $input): array
    {
        $input['bank_id'] = filled($input['bank_id'] ?? null) ? $input['bank_id'] : null;
        $data = validator($input, Income::rules($user), self::messages(), self::attributes())->validate();
        $data['concept'] = trim((string) $data['concept']);
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;
        $data['bank_id'] = filled($data['bank_id'] ?? null) ? $data['bank_id'] : null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function expense(User $user, array $input): array
    {
        $method = $input['payment_method'] ?? null;
        $input['payment_method'] = is_string($method) && $method !== '' ? $method : null;
        $input['bank_id'] = filled($input['bank_id'] ?? null) ? $input['bank_id'] : null;

        if (! Expense::usesBank($input['payment_method'])) {
            $input['bank_id'] = null;
        }

        $data = validator($input, Expense::rules($user), self::messages(), self::attributes())->validate();
        $data['concept'] = trim((string) $data['concept']);
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;
        $data['payment_method'] = filled($data['payment_method'] ?? null) ? $data['payment_method'] : null;
        $data['bank_id'] = Expense::usesBank(is_string($data['payment_method']) ? $data['payment_method'] : null) && filled($data['bank_id'] ?? null)
            ? $data['bank_id']
            : null;

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private static function messages(): array
    {
        return [
            'occurred_on.required' => 'Elige la fecha.',
            'occurred_on.date' => 'La fecha no es válida.',
            'concept.required' => 'Escribe el concepto.',
            'category.required' => 'Elige una categoría.',
            'category.in' => 'Esa categoría no existe.',
            'currency.required' => 'Elige la moneda.',
            'currency.in' => 'Esa moneda no está permitida.',
            'amount.required' => 'Escribe el monto.',
            'amount.gt' => 'El monto debe ser mayor a 0.',
            'amount.decimal' => 'El monto acepta hasta 2 decimales.',
            'payment_method.in' => 'Ese medio de pago no existe.',
            'bank_id.integer' => 'Ese banco no está en tu lista.',
            'bank_id.exists' => 'Ese banco no está en tu lista.',
            'status.required' => 'Indica si ya está pagado.',
            'status.in' => 'Ese estado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function attributes(): array
    {
        return [
            'occurred_on' => 'fecha',
            'concept' => 'concepto',
            'category' => 'categoría',
            'currency' => 'moneda',
            'amount' => 'monto',
            'notes' => 'nota',
            'payment_method' => 'medio de pago',
            'bank_id' => 'banco',
            'status' => 'estado',
        ];
    }
}
