<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rubros viejos que ya no están en el formulario.
     * Vivienda no se revierte: hogar quedó mezclado con viviendas reales.
     *
     * @var array<string, string>
     */
    private array $map = [
        'comida' => 'alimentacion',
        'salud' => 'seguros_salud',
        'educacion' => 'colegios',
        'hogar' => 'vivienda',
        'ocio' => 'entretenimiento',
    ];

    public function up(): void
    {
        foreach ($this->map as $from => $to) {
            $occupied = DB::table('expense_budgets')
                ->where('category', $to)
                ->pluck('user_id');

            DB::table('expense_budgets')
                ->where('category', $from)
                ->whereIn('user_id', $occupied)
                ->delete();

            DB::table('expense_budgets')->where('category', $from)->update(['category' => $to]);
            DB::table('expenses')->where('category', $from)->update(['category' => $to]);
            DB::table('recurring_entries')->where('category', $from)->update(['category' => $to]);
        }
    }

    public function down(): void
    {
        $reversible = [
            'alimentacion' => 'comida',
            'seguros_salud' => 'salud',
            'colegios' => 'educacion',
            'entretenimiento' => 'ocio',
        ];

        foreach ($reversible as $from => $to) {
            DB::table('expense_budgets')->where('category', $from)->update(['category' => $to]);
            DB::table('expenses')->where('category', $from)->update(['category' => $to]);
            DB::table('recurring_entries')->where('category', $from)->update(['category' => $to]);
        }
    }
};
