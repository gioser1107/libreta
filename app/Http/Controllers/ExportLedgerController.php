<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLedgerController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        Permissions::authorize('ingresos', 'view');
        Permissions::authorize('egresos', 'view');

        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $start = now()->setDate((int) $data['year'], (int) $data['month'], 1);
        $from = $start->copy()->startOfMonth()->toDateString();
        $to = $start->copy()->endOfMonth()->toDateString();
        $filename = sprintf('libreta-%d-%02d.csv', $data['year'], $data['month']);

        $rows = $this->rows($user, $from, $to);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Tipo', 'Concepto', 'Categoría', 'Moneda', 'Monto', 'Dólares', 'Bolívares', 'Banco', 'Estado', 'Nota']);

            foreach ($rows as $row) {
                fputcsv($out, array_map($this->cell(...), $row));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<list<string|null>>
     */
    private function rows(User $user, string $from, string $to): array
    {
        $rows = [];

        $incomes = Income::query()
            ->ownedBy($user)
            ->with('bank')
            ->whereBetween('occurred_on', [$from, $to])
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        foreach ($incomes as $income) {
            $rows[] = [
                $income->occurred_on->toDateString(),
                'Ingreso',
                $income->concept,
                Income::CATEGORIES[$income->category] ?? $income->category,
                $income->currency,
                (string) $income->amount,
                (string) $income->amount_usd,
                (string) $income->amount_ves,
                $income->bank?->name,
                null,
                $income->notes,
            ];
        }

        $expenses = Expense::query()
            ->ownedBy($user)
            ->with('bank')
            ->whereBetween('occurred_on', [$from, $to])
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $rows[] = [
                $expense->occurred_on->toDateString(),
                'Egreso',
                $expense->concept,
                Expense::CATEGORIES[$expense->category] ?? $expense->category,
                $expense->currency,
                (string) $expense->amount,
                (string) $expense->amount_usd,
                (string) $expense->amount_ves,
                $expense->bank?->name,
                Expense::STATUSES[$expense->status] ?? $expense->status,
                $expense->notes,
            ];
        }

        $transfers = Transfer::query()
            ->ownedBy($user)
            ->with(['fromBank', 'toBank'])
            ->whereBetween('occurred_on', [$from, $to])
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        foreach ($transfers as $transfer) {
            $rows[] = [
                $transfer->occurred_on->toDateString(),
                'Traspaso',
                ($transfer->fromBank?->name ?? 'Banco').' → '.($transfer->toBank?->name ?? 'Banco'),
                'Traspaso',
                $transfer->currency,
                (string) $transfer->amount,
                (string) $transfer->amount_usd,
                (string) $transfer->amount_ves,
                ($transfer->fromBank?->name ?? '').' → '.($transfer->toBank?->name ?? ''),
                null,
                $transfer->notes,
            ];
        }

        return $rows;
    }

    private function cell(?string $value): string
    {
        $text = (string) ($value ?? '');

        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$text;
        }

        return $text;
    }
}
