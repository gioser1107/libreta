<?php

namespace App\Actions\Ledger;

use App\Models\Expense;
use App\Models\Income;

class LedgerInput
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function income(array $input): array
    {
        $data = validator($input, Income::rules(), self::messages(), self::attributes())->validate();
        $data['concept'] = trim((string) $data['concept']);
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function expense(array $input): array
    {
        $data = validator($input, Expense::rules(), self::messages(), self::attributes())->validate();
        $data['concept'] = trim((string) $data['concept']);
        $data['notes'] = filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null;
        $data['payment_method'] = filled($data['payment_method'] ?? null) ? $data['payment_method'] : null;

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
            'status' => 'estado',
        ];
    }
}
