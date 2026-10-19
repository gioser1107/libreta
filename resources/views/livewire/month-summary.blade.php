<div class="lb-page lb-home">
    <header class="lb-head lb-head-period">
        <div class="lb-head-row">
            <h1>Resumen</h1>
            <a href="{{ route('ledger.export', ['month' => $month, 'year' => $year]) }}" class="lb-textbtn">Exportar</a>
        </div>
        @include('livewire.partials.month-switcher')
    </header>

    <div class="lb-top">
    <section class="lb-hero" aria-label="{{ $summary['opening_usd'] != 0 || $summary['opening_ves'] != 0 ? 'Saldo acumulado' : 'Saldo del mes' }}">
        @if($summary['opening_usd'] != 0 || $summary['opening_ves'] != 0)
            <p class="lb-hero-kicker">Tienes</p>
            <p class="lb-hero-amount">{{ \App\Support\Money::format($summary['available_usd'], 'USD') }}</p>
            <p class="lb-hero-ves">{{ \App\Support\Money::format($summary['available_ves'], 'VES') }}</p>
        @else
            <p class="lb-hero-kicker">Saldo de {{ mb_strtolower($months[$month] ?? 'este mes') }}</p>
            <p class="lb-hero-amount">{{ \App\Support\Money::format($summary['balance_usd'], 'USD') }}</p>
            <p class="lb-hero-ves">{{ \App\Support\Money::format($summary['balance_ves'], 'VES') }}</p>
        @endif

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

        @if($summary['opening_usd'] != 0 || $summary['opening_ves'] != 0)
            <p class="lb-hero-note">{{ $carriedFrom }} dejó {{ \App\Support\Money::format($summary['opening_usd'], 'USD') }}<br>{{ $months[$month] }} {{ \App\Support\Money::format($summary['balance_usd'], 'USD') }}</p>
        @endif

        @if($pace['caption'])
            <p class="lb-hero-note">{{ $pace['caption'] }}</p>
        @endif

        @if($summary['exchange_spread_usd'] != 0)
            <p class="lb-hero-note">
                @if($summary['exchange_spread_usd'] > 0)
                    En el cambio perdiste {{ \App\Support\Money::format($summary['exchange_spread_usd'], 'USD') }}
                @else
                    En el cambio ganaste {{ \App\Support\Money::format(abs($summary['exchange_spread_usd']), 'USD') }}
                @endif
            </p>
        @endif
    </section>

    <section class="lb-metrics" aria-label="Totales del mes">
        <a href="{{ route('incomes.index', ['month' => $month, 'year' => $year]) }}" wire:navigate class="lb-metric">
            <span>Ingresos</span>
            <strong>{{ \App\Support\Money::format($summary['income_usd'], 'USD') }}</strong>
            @if($pace['daily_income'] !== null)
                <em>≈ {{ \App\Support\Money::format($pace['daily_income'], 'USD') }} / día</em>
            @else
                <em>{{ $pace['income_count'] === 1 ? '1 movimiento' : $pace['income_count'].' movimientos' }}</em>
            @endif
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
    </div>

    <section class="lb-block" aria-label="Bancos">
        <div class="lb-block-head">
            <h2 class="lb-section-title">Bancos</h2>
            <a href="{{ route('banks') }}" wire:navigate class="lb-textbtn">Gestionar</a>
        </div>
        <div class="lb-panel">
            @if($accounts->isEmpty())
                <a href="{{ route('banks') }}" wire:navigate class="lb-entry">
                    <span class="lb-entry-main">
                        <span class="lb-entry-title">Agrega un banco</span>
                    </span>
                </a>
            @else
                <div class="lb-list">
                    @foreach($accounts as $account)
                        <a href="{{ route('banks') }}" wire:navigate wire:key="account-{{ $account['bank']->id }}" class="lb-entry">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $account['bank']->name }}</span>
                            </span>
                            <span class="lb-entry-amt">
                                @if($account['label'] === 'Sin saldo')
                                    <small>Sin saldo</small>
                                @else
                                    {{ $account['label'] }}
                                    @php($dollarsInBolivares = \App\Support\Money::vesEquivalent($account['balances']['USD'], $usdRate))
                                    @if($dollarsInBolivares !== null)
                                        <small>≈ {{ \App\Support\Money::format($dollarsInBolivares, 'VES') }}</small>
                                    @endif
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="lb-block" aria-label="Divisas">
        <div class="lb-block-head">
            <h2 class="lb-section-title">Divisas</h2>
            <a href="{{ route('divisas') }}" wire:navigate class="lb-textbtn">Gestionar</a>
        </div>
        <div class="lb-panel">
            @if($holdings === [])
                <a href="{{ route('divisas') }}" wire:navigate class="lb-entry">
                    <span class="lb-entry-main">
                        <span class="lb-entry-title">Agrega un lugar</span>
                    </span>
                </a>
            @else
                <div class="lb-list">
                    @foreach($holdings as $holding)
                        <a href="{{ route('divisas') }}" wire:navigate wire:key="divisa-{{ $holding['id'] }}" class="lb-entry">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $holding['label'] }}</span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ \App\Support\Money::format($holding['balances']['USD'], 'USD') }}
                                @if($holding['balances']['EUR'] != 0)
                                    <small>{{ \App\Support\Money::format($holding['balances']['EUR'], 'EUR') }}</small>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="lb-highlights" aria-label="Destacados del mes">
        @foreach($cards as $card)
            @if($card['move'])
                <a href="{{ $card['move']['href'] }}" wire:navigate wire:key="card-{{ $loop->index }}" class="lb-highlight">
                    <span class="lb-kicker">{{ $card['label'] }}</span>
                    <span class="lb-highlight-row">
                        <x-move-mark :kind="$card['move']['kind']" />
                        <span class="lb-highlight-copy">
                            <span class="lb-highlight-title">{{ $card['move']['concept'] }}</span>
                            <span class="lb-highlight-meta">{{ $card['move']['when'] }} · {{ $card['move']['meta'] }}</span>
                        </span>
                        <span class="lb-highlight-amt">
                            {{ $card['move']['kind'] === 'out' ? '−' : '+' }}{{ \App\Support\Money::format($card['move']['usd'], 'USD') }}
                            @if($card['move']['currency'] !== 'USD')
                                <small>{{ $card['move']['native'] }}</small>
                            @endif
                        </span>
                    </span>
                </a>
            @else
                <div wire:key="card-{{ $loop->index }}" class="lb-highlight is-empty">
                    <span class="lb-kicker">{{ $card['label'] }}</span>
                    <span class="lb-highlight-title">{{ $card['empty'] }}</span>
                </div>
            @endif
        @endforeach
    </section>

    @if($incomeCategories !== [] || $categories !== [])
        <div class="lb-pair">
            @if($incomeCategories !== [])
                <section class="lb-block" aria-label="Ingresos por categoría">
                    <div class="lb-block-head">
                        <h2 class="lb-section-title">De dónde vino</h2>
                    </div>
                    <div class="lb-panel">
                        <div class="lb-cats">
                            @foreach($incomeCategories as $category)
                                <div wire:key="incat-{{ $category['key'] }}">
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

            @if($categories !== [])
                <section class="lb-block" aria-label="Gastos por categoría">
                    <div class="lb-block-head">
                        <h2 class="lb-section-title">En qué se fue</h2>
                    </div>
                    <div class="lb-panel">
                        <div class="lb-cats">
                            @foreach($categories as $category)
                                <div wire:key="cat-{{ $category['key'] }}">
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
        </div>
    @endif

    <div class="lb-lower">
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
                            <x-move-mark :kind="$move['kind']" />
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $move['concept'] }}</span>
                                <span class="lb-entry-meta">{{ $move['when'] }} · {{ match ($move['kind']) { 'in' => 'Ingreso', 'move' => 'Traspaso', 'swap' => 'Cambio', default => 'Egreso' } }} · {{ $move['meta'] }}</span>
                            </span>
                            <span class="lb-entry-amt">
                                @if($move['kind'] === 'swap')
                                    {{ $move['usd'] > 0 ? '−' : ($move['usd'] < 0 ? '+' : '') }}{{ \App\Support\Money::format(abs($move['usd']), 'USD') }}
                                @else
                                    {{ $move['kind'] === 'out' ? '−' : ($move['kind'] === 'in' ? '+' : '') }}{{ \App\Support\Money::format($move['usd'], 'USD') }}
                                @endif
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
</div>
