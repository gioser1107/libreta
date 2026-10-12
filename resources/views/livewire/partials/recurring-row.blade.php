<button type="button" wire:click="edit({{ $row['id'] }})" wire:key="recurring-{{ $row['id'] }}" class="lb-entry">
    <x-move-mark :kind="$row['kind'] === 'income' ? 'in' : 'out'" />
    <span class="lb-entry-main">
        <span class="lb-entry-title">{{ $row['concept'] }}</span>
        <span class="lb-entry-meta">
            {{ $row['meta'] }}
            @if($row['badge'] !== '')
                <span @class(['lb-badge', $row['badge_class']])>{{ $row['badge'] }}</span>
            @endif
        </span>
    </span>
    <span class="lb-entry-amt">
        {{ $row['kind'] === 'expense' ? '−' : '+' }}@if($row['usd'] !== null){{ \App\Support\Money::format($row['usd'], 'USD') }}@else{{ \App\Support\Money::format($row['amount'], $row['currency']) }}@endif
        @if($row['usd'] !== null && $row['currency'] !== 'USD')
            <small>{{ \App\Support\Money::format($row['amount'], $row['currency']) }}</small>
        @endif
    </span>
</button>
