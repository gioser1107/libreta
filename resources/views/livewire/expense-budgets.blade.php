<div>
    <header class="lb-head">
        <div class="lb-head-row">
            <div>
                <x-back :href="route('profile')" />
                <h1>Topes</h1>
            </div>
            <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
        </div>
    </header>

    @if($caps === [])
        <div class="lb-panel">
            <div class="lb-empty">
                <p>Todavía no hay topes.</p>
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
            </div>
        </div>
    @else
        <div class="lb-top">
            <section class="lb-hero" aria-label="Lo que queda del tope">
                @if($overview['left'] < 0)
                    <p class="lb-hero-kicker">Te pasaste</p>
                    <p class="lb-hero-amount">−{{ \App\Support\Money::format(abs($overview['left']), 'USD') }}</p>
                @else
                    <p class="lb-hero-kicker">Te queda</p>
                    <p class="lb-hero-amount">{{ \App\Support\Money::format($overview['left'], 'USD') }}</p>
                @endif
                <p class="lb-hero-note">{{ $monthLabel }} · tope {{ \App\Support\Money::format($overview['limit'], 'USD') }}</p>
            </section>

            <section class="lb-metrics" aria-label="Gastado y tope">
                <div class="lb-metric">
                    <span>Gastado</span>
                    <strong>{{ \App\Support\Money::format($overview['spent'], 'USD') }}</strong>
                    <em>
                        @if($overview['over'] === 0)
                            Dentro del tope
                        @elseif($overview['over'] === 1)
                            1 pasado
                        @else
                            {{ $overview['over'] }} pasados
                        @endif
                    </em>
                </div>
                <div class="lb-metric">
                    <span>Tope</span>
                    <strong>{{ \App\Support\Money::format($overview['limit'], 'USD') }}</strong>
                    <em>{{ $monthLabel }}</em>
                </div>
            </section>
        </div>

        <div class="lb-block">
            <div class="lb-panel">
                @foreach($caps as $cap)
                    <button type="button" wire:key="cap-{{ $cap['key'] }}" wire:click="edit('{{ $cap['key'] }}')" class="lb-cap">
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">{{ $cap['label'] }}</span>
                            <span class="lb-entry-meta">
                                {{ \App\Support\Money::format($cap['usd'], 'USD') }} de {{ \App\Support\Money::format($cap['limit'], 'USD') }}
                                @if($cap['over'])
                                    <span class="lb-badge is-pending">pasado</span>
                                @endif
                            </span>
                        </span>
                        <span class="lb-track" aria-hidden="true">
                            <span style="width: {{ $cap['width'] }}%"></span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if($showModal)
        @teleport('body')
        <div class="lb-overlay" wire:click.self="closeModal">
            <form wire:submit="saveBudget" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="budget-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <x-back wire:click="closeModal" />
                    <h2 id="budget-dialog-title">{{ $editingCategory ? 'Editar tope' : 'Nuevo tope' }}</h2>
                </div>
                <div class="lb-form">
                    <label class="lb-field">
                        <span class="lb-label">Categoría</span>
                        <select wire:model="budgetCategory" class="lb-control">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="lb-field">
                        <span class="lb-label">Tope en dólares</span>
                        <input wire:model="budgetAmount" type="text" inputmode="decimal" class="lb-control" placeholder="0" aria-label="Tope en dólares" autofocus>
                        @error('limit_usd') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <p class="lb-note">Se compara con lo que gastes este mes.</p>
                </div>
                <div class="lb-dialog-foot">
                    @if($confirmingRemoval)
                        <p class="lb-dialog-ask">¿Quitar este tope?</p>
                        <button type="button" wire:click="cancelRemoval" class="lb-btn lb-btn-ghost">No</button>
                        <button type="button" wire:click="clearBudget('{{ $editingCategory }}')" class="lb-btn lb-btn-danger-solid" wire:loading.attr="disabled" wire:target="clearBudget">Quitar</button>
                    @else
                        @if($editingCategory)
                            <button type="button" wire:click="askRemoval" class="lb-textbtn is-danger">Quitar</button>
                        @endif
                        <button type="button" wire:click="closeModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                        <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="saveBudget">
                            <span wire:loading.remove wire:target="saveBudget">{{ $editingCategory ? 'Guardar' : 'Agregar' }}</span>
                            <span wire:loading wire:target="saveBudget">Guardando…</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
        @endteleport
    @endif
</div>
