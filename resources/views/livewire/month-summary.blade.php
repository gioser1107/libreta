<div class="lb-page">
    <header class="lb-head">
        <div>
            <h1>Resumen</h1>
            @include('livewire.partials.month-switcher')
        </div>
    </header>

    <section class="lb-stats" aria-label="Totales del mes">
        <div class="lb-stat">
            <span>Saldo</span>
            <strong>{{ \App\Support\Money::format($summary['balance_usd'], 'USD') }}</strong>
            <em>{{ \App\Support\Money::format($summary['balance_ves'], 'VES') }}</em>
        </div>
        <a href="{{ route('incomes.index', ['month' => $month, 'year' => $year]) }}" wire:navigate class="lb-stat">
            <span>Ingresos</span>
            <strong>{{ \App\Support\Money::format($summary['income_usd'], 'USD') }}</strong>
        </a>
        <a href="{{ route('expenses.index', ['month' => $month, 'year' => $year]) }}" wire:navigate class="lb-stat">
            <span>Egresos</span>
            <strong>{{ \App\Support\Money::format($summary['expense_usd'], 'USD') }}</strong>
        </a>
        <a href="{{ route('expenses.index', ['month' => $month, 'year' => $year, 'filterStatus' => 'pending']) }}" wire:navigate class="lb-stat">
            <span>Por pagar</span>
            <strong>{{ \App\Support\Money::format($summary['expense_pending_usd'], 'USD') }}</strong>
        </a>
    </section>

    <section class="lb-panel">
        @if($moves->isEmpty())
            <p class="lb-empty">No hay movimientos en {{ mb_strtolower($months[$month] ?? 'este mes') }} {{ $year }}.</p>
        @else
            <div class="lb-list">
                @foreach($moves as $move)
                    <a href="{{ $move['href'] }}" wire:navigate wire:key="move-{{ $move['kind'] }}-{{ $move['id'] }}" class="lb-entry">
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">{{ $move['concept'] }}</span>
                            <span class="lb-entry-meta">{{ $move['when'] }} · {{ $move['kind'] === 'in' ? 'Ingreso' : 'Egreso' }} · {{ $move['meta'] }}</span>
                        </span>
                        <span class="lb-entry-amt">{{ $move['kind'] === 'in' ? '' : '−' }}{{ \App\Support\Money::format($move['usd'], 'USD') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
