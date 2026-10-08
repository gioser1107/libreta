<div class="lb-page lb-home">
    <header class="lb-head">
        <div>
            <h1>Resumen</h1>
            @include('livewire.partials.month-switcher')
        </div>
    </header>

    <section class="lb-hero" aria-label="Saldo del mes">
        <p class="lb-hero-kicker">Saldo de {{ mb_strtolower($months[$month] ?? 'este mes') }}</p>
        <p class="lb-hero-amount">{{ \App\Support\Money::format($summary['balance_usd'], 'USD') }}</p>
        <p class="lb-hero-ves">{{ \App\Support\Money::format($summary['balance_ves'], 'VES') }}</p>

        @if($summary['income_usd'] > 0 || $summary['expense_usd'] > 0)
            <div class="lb-splitbar" aria-hidden="true">
                <span class="is-in" style="flex: {{ $summary['income_usd'] }} 1 0"></span>
                <span class="is-out" style="flex: {{ $summary['expense_usd'] }} 1 0"></span>
            </div>
            <div class="lb-hero-legend">
                <span>Ingresos {{ \App\Support\Money::format($summary['income_usd'], 'USD') }}</span>
                <span>Egresos {{ \App\Support\Money::format($summary['expense_usd'], 'USD') }}</span>
            </div>
        @endif

        @if($pace['caption'])
            <p class="lb-hero-note">{{ $pace['caption'] }}</p>
        @endif
    </section>

    <section class="lb-metrics" aria-label="Totales del mes">
        <a href="{{ route('incomes.index', ['month' => $month, 'year' => $year]) }}" wire:navigate class="lb-metric">
            <span>Ingresos</span>
            <strong>{{ \App\Support\Money::format($summary['income_usd'], 'USD') }}</strong>
            <em>{{ $pace['income_count'] === 1 ? '1 movimiento' : $pace['income_count'].' movimientos' }}</em>
        </a>
        <a href="{{ route('expenses.index', ['month' => $month, 'year' => $year]) }}" wire:navigate class="lb-metric">
            <span>Egresos</span>
            <strong>{{ \App\Support\Money::format($summary['expense_usd'], 'USD') }}</strong>
            @if($pace['daily_expense'] !== null)
                <em>≈ {{ \App\Support\Money::format($pace['daily_expense'], 'USD') }} / día</em>
            @else
                <em>{{ $pace['expense_count'] === 1 ? '1 movimiento' : $pace['expense_count'].' movimientos' }}</em>
            @endif
        </a>
        <a href="{{ route('expenses.index', ['month' => $month, 'year' => $year, 'filterStatus' => 'pending']) }}" wire:navigate class="lb-metric">
            <span>Por pagar</span>
            <strong>{{ \App\Support\Money::format($summary['expense_pending_usd'], 'USD') }}</strong>
            @if($pace['pending_count'] === 0)
                <em>Al día</em>
            @elseif($pace['pending_count'] === 1)
                <em>1 cuenta</em>
            @else
                <em>{{ $pace['pending_count'] }} cuentas</em>
            @endif
        </a>
    </section>

    <section class="lb-highlights" aria-label="Destacados del mes">
        @foreach($cards as $card)
            @if($card['move'])
                <a href="{{ $card['move']['href'] }}" wire:navigate wire:key="card-{{ $loop->index }}" class="lb-highlight">
                    <span class="lb-kicker">{{ $card['label'] }}</span>
                    <span class="lb-highlight-title">{{ $card['move']['concept'] }}</span>
                    <span class="lb-highlight-meta">{{ $card['move']['when'] }} · {{ $card['move']['meta'] }}</span>
                    <span class="lb-highlight-amt">{{ $card['move']['kind'] === 'in' ? '' : '−' }}{{ \App\Support\Money::format($card['move']['usd'], 'USD') }}</span>
                </a>
            @else
                <div wire:key="card-{{ $loop->index }}" class="lb-highlight is-empty">
                    <span class="lb-kicker">{{ $card['label'] }}</span>
                    <span class="lb-highlight-title">{{ $card['empty'] }}</span>
                </div>
            @endif
        @endforeach
    </section>

    @if($categories !== [])
        <section class="lb-block" aria-label="Gastos por categoría">
            <div class="lb-block-head">
                <h2 class="lb-section-title">En qué se fue</h2>
            </div>
            <div class="lb-panel">
                <div class="lb-cats">
                    @foreach($categories as $category)
                        <div wire:key="cat-{{ $category['label'] }}">
                            <div class="lb-cat-top">
                                <strong>{{ $category['label'] }}</strong>
                                <span>{{ \App\Support\Money::format($category['usd'], 'USD') }} · {{ $category['share'] }}%</span>
                            </div>
                            <div class="lb-track" aria-hidden="true">
                                <span style="width: {{ $category['width'] }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="lb-block" aria-label="Últimos movimientos">
        <div class="lb-block-head">
            <h2 class="lb-section-title">Últimos movimientos</h2>
            @if($moves->count() > $recent->count())
                <span class="lb-help">{{ $recent->count() }} de {{ $moves->count() }}</span>
            @endif
        </div>
        <div class="lb-panel">
            @if($recent->isEmpty())
                <p class="lb-empty">No hay movimientos en {{ mb_strtolower($months[$month] ?? 'este mes') }} {{ $year }}.</p>
            @else
                <div class="lb-list">
                    @foreach($recent as $move)
                        <a href="{{ $move['href'] }}" wire:navigate wire:key="move-{{ $move['kind'] }}-{{ $move['id'] }}" class="lb-entry">
                            <span @class(['lb-mark', 'is-in' => $move['kind'] === 'in', 'is-out' => $move['kind'] === 'out']) aria-hidden="true">{{ $move['kind'] === 'in' ? '+' : '−' }}</span>
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $move['concept'] }}</span>
                                <span class="lb-entry-meta">{{ $move['when'] }} · {{ $move['kind'] === 'in' ? 'Ingreso' : 'Egreso' }} · {{ $move['meta'] }}</span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ $move['kind'] === 'in' ? '' : '−' }}{{ \App\Support\Money::format($move['usd'], 'USD') }}
                                @if($move['currency'] !== 'USD')
                                    <small>{{ $move['native'] }}</small>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
