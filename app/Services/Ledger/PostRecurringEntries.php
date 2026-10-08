<?php

namespace App\Services\Ledger;

use App\Exceptions\LedgerException;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringEntry;
use App\Models\RecurringPost;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PostRecurringEntries
{
    public function __construct(private readonly MoneyQuoteService $quotes) {}

    public function postDue(?CarbonInterface $today = null): int
    {
        $today = $this->today($today);
        $posted = 0;

        $userIds = RecurringEntry::query()
            ->where('active', true)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $posted += $this->postFor($user, $today);
        }

        return $posted;
    }

    public function postFor(User $user, ?CarbonInterface $today = null): int
    {
        $today = $this->today($today);
        $period = $today->format('Y-m');
        $posted = 0;

        $entries = RecurringEntry::query()
            ->ownedBy($user)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $day = min($entry->day_of_month, $today->daysInMonth);

            if ($today->day < $day) {
                continue;
            }

            if ($this->alreadyPosted($entry, $period)) {
                continue;
            }

            try {
                if ($this->postOne($user, $entry, $today->copy()->day($day)->toDateString(), $period)) {
                    $posted++;
                }
            } catch (LedgerException $exception) {
                report($exception);
            }
        }

        return $posted;
    }

    private function today(?CarbonInterface $today): CarbonInterface
    {
        return ($today ?? now('America/Caracas'))->copy()->timezone('America/Caracas')->startOfDay();
    }

    private function alreadyPosted(RecurringEntry $entry, string $period): bool
    {
        return RecurringPost::query()
            ->where('recurring_entry_id', $entry->id)
            ->where('period', $period)
            ->exists();
    }

    private function postOne(User $user, RecurringEntry $entry, string $date, string $period): bool
    {
        $quoted = $this->quotes->quote($date, $entry->currency, $entry->amount);

        try {
            return DB::transaction(function () use ($user, $entry, $date, $period, $quoted): bool {
                $posted = RecurringPost::query()
                    ->where('recurring_entry_id', $entry->id)
                    ->where('period', $period)
                    ->lockForUpdate()
                    ->exists();

                if ($posted) {
                    return false;
                }

                $post = new RecurringPost([
                    'recurring_entry_id' => $entry->id,
                    'period' => $period,
                ]);

                if ($entry->kind === RecurringEntry::KIND_INCOME) {
                    $income = Income::query()->create([
                        'user_id' => $user->id,
                        'bank_id' => $entry->bank_id,
                        'occurred_on' => $date,
                        'concept' => $entry->concept,
                        'category' => $entry->category,
                        'notes' => $entry->notes,
                        ...$quoted,
                    ]);
                    $post->income_id = $income->id;
                } else {
                    $expense = Expense::query()->create([
                        'user_id' => $user->id,
                        'bank_id' => $entry->bank_id,
                        'occurred_on' => $date,
                        'concept' => $entry->concept,
                        'category' => $entry->category,
                        'payment_method' => $entry->payment_method,
                        'status' => $entry->status,
                        'notes' => $entry->notes,
                        ...$quoted,
                    ]);
                    $post->expense_id = $expense->id;
                }

                $post->save();

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
