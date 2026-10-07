<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

trait FiltersByMonth
{
    #[Url]
    public int $month = 0;

    #[Url]
    public int $year = 0;

    public function mountMonth(): void
    {
        if ($this->month < 1 || $this->month > 12) {
            $this->month = (int) now()->month;
        }

        if ($this->year < 2020 || $this->year > 2100) {
            $this->year = (int) now()->year;
        }
    }

    public function updatedMonth(): void
    {
        $this->resetLedgerPage();
    }

    public function updatedYear(): void
    {
        $this->resetLedgerPage();
    }

    public function shiftMonth(int $step): void
    {
        $step = $step < 0 ? -1 : 1;
        $cursor = now()->setDate($this->year, $this->month, 1)->addMonths($step);

        if ($cursor->year < 2020 || $cursor->year > 2100) {
            return;
        }

        $this->year = $cursor->year;
        $this->month = $cursor->month;
        $this->resetLedgerPage();
    }

    protected function resetLedgerPage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
