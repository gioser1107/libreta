<div>
    <header class="lb-head">
        <div class="lb-head-row">
            <div>
                <x-back :href="route('dashboard')" />
                <h1>Bancos</h1>
            </div>
            <div class="lb-head-actions">
                <button type="button" wire:click="$dispatch('open-exchange')" class="lb-btn lb-btn-ghost">Cambiar</button>
                <button type="button" wire:click="$dispatch('open-transfer')" class="lb-btn lb-btn-ghost">Pasar</button>
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
            </div>
        </div>
    </header>

    @if($banks->isEmpty())
        <div class="lb-panel">
            <div class="lb-empty">
                <p>Todavía no tienes bancos.</p>
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
            </div>
        </div>
    @else
        <div class="lb-top">
            <section class="lb-hero" aria-label="Saldo en dólares">
                <p class="lb-hero-kicker">En tus bancos</p>
                <p class="lb-hero-amount">{{ \App\Support\Money::format($totals['USD'], 'USD') }}</p>
                <p class="lb-hero-note">{{ $banks->count() === 1 ? '1 banco' : $banks->count().' bancos' }}</p>
            </section>

            <section class="lb-metrics" aria-label="Otras monedas">
                <div class="lb-metric">
                    <span>Bolívares</span>
                    <strong>{{ \App\Support\Money::format($totals['VES'], 'VES') }}</strong>
                </div>
                <div class="lb-metric">
                    <span>Euros</span>
                    <strong>{{ \App\Support\Money::format($totals['EUR'], 'EUR') }}</strong>
                </div>
            </section>
        </div>

        <div class="lb-bank-board" aria-label="Saldo de cada banco">
            @foreach($banks as $bank)
                @php($balance = $balances[$bank->id]['balances'] ?? ['VES' => 0, 'USD' => 0, 'EUR' => 0])
                <button type="button" wire:key="bank-{{ $bank->id }}" wire:click="edit({{ $bank->id }})" class="lb-bank-card">
                    <span class="lb-bank-card-name">{{ $bank->name }}</span>
                    <span class="lb-bank-card-figures">
                        <span><small>Dólares</small><strong>{{ \App\Support\Money::format($balance['USD'], 'USD') }}</strong></span>
                        <span><small>Bolívares</small><strong>{{ \App\Support\Money::format($balance['VES'], 'VES') }}</strong></span>
                        <span><small>Euros</small><strong>{{ \App\Support\Money::format($balance['EUR'], 'EUR') }}</strong></span>
                    </span>
                </button>
            @endforeach
        </div>
    @endif

    @if($showModal)
        @teleport('body')
        <div class="lb-overlay" wire:click.self="closeModal">
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="bank-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <x-back wire:click="closeModal" />
                    <h2 id="bank-dialog-title">{{ $editingId ? 'Editar banco' : 'Nuevo banco' }}</h2>
                </div>
                <div class="lb-form">
                    <label class="lb-field">
                        <span class="lb-label">Nombre</span>
                        <input wire:model="name" type="text" maxlength="60" class="lb-control" placeholder="Banesco, Mercantil…" autofocus>
                        @error('name') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <p class="lb-note">Saldo inicial: lo que ya tenías en este banco antes de anotarlo aquí.</p>
                    <label class="lb-field">
                        <span class="lb-label">Bolívares</span>
                        <input wire:model="openingVes" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                        @error('opening_ves') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="lb-field">
                        <span class="lb-label">Dólares</span>
                        <input wire:model="openingUsd" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                        @error('opening_usd') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="lb-field">
                        <span class="lb-label">Euros</span>
                        <input wire:model="openingEur" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                        @error('opening_eur') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="lb-dialog-foot">
                    @error('removal')
                        <p class="lb-error">{{ $message }}</p>
                    @enderror
                    @if($confirmingRemovalId)
                        <p class="lb-dialog-ask">¿Borrar {{ $banks->firstWhere('id', $confirmingRemovalId)?->name }}?</p>
                        <button type="button" wire:click="cancelRemoval" class="lb-btn lb-btn-ghost">No</button>
                        <button type="button" wire:click="delete({{ $confirmingRemovalId }})" class="lb-btn lb-btn-danger-solid" wire:loading.attr="disabled" wire:target="delete">Borrar</button>
                    @else
                        @if($editingId)
                            <button type="button" wire:click="askRemoval({{ $editingId }})" class="lb-textbtn is-danger">Borrar</button>
                        @endif
                        <button type="button" wire:click="closeModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                        <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Guardar' : 'Agregar' }}</span>
                            <span wire:loading wire:target="save">Guardando…</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
        @endteleport
    @endif
</div>
