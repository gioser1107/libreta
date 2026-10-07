<div class="lb-month">
    <button type="button" wire:click="shiftMonth(-1)" aria-label="Mes anterior">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <span>{{ $months[$month] ?? '' }} {{ $year }}</span>
    <button type="button" wire:click="shiftMonth(1)" aria-label="Mes siguiente">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>
</div>
