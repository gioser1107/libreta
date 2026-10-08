<div class="lb-page">
    <header class="lb-head lb-head-period">
        <div class="lb-head-row">
            <h1>Egresos</h1>
            @can('egresos.create')
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo egreso</button>
            @endcan
        </div>
        @include('livewire.partials.month-switcher')
    </header>

    <section class="lb-stats is-3" aria-label="Total de egresos">
        <div class="lb-stat">
            <span>Total en dólares</span>
            <strong>{{ \App\Support\Money::format($monthUsd, 'USD') }}</strong>
        </div>
        <div class="lb-stat">
            <span>Total en bolívares</span>
            <strong>{{ \App\Support\Money::format($monthVes, 'VES') }}</strong>
        </div>
        <div class="lb-stat">
            <span>Por pagar</span>
            <strong @class(['is-warn' => $pendingUsd > 0])>{{ \App\Support\Money::format($pendingUsd, 'USD') }}</strong>
        </div>
    </section>

    <div class="lb-filters">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por concepto" class="lb-control grow" aria-label="Buscar por concepto">
        <select wire:model.live="filterStatus" class="lb-control" aria-label="Estado">
            <option value="all">Todos los estados</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterCategory" class="lb-control" aria-label="Categoría">
            <option value="all">Todas las categorías</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @error('search')
        <p class="lb-error mb-3">{{ $message }}</p>
    @enderror

    <section class="lb-panel">
        @if($rows->isEmpty())
            <div class="lb-empty">
                @if($search !== '' || $filterCategory !== 'all' || $filterStatus !== 'all')
                    <p>Ningún egreso coincide.</p>
                @else
                    <p>No hay egresos en {{ mb_strtolower($months[$month] ?? 'este mes') }} {{ $year }}.</p>
                @endif
                @can('egresos.create')
                    <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo egreso</button>
                @endcan
            </div>
        @else
            <div class="lb-list">
                @foreach($rows as $row)
                    @can('egresos.edit')
                        <button type="button" wire:click="edit({{ $row->id }})" wire:key="expense-{{ $row->id }}" class="lb-entry">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">
                                    {{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}@if($row->payment_method) · {{ $methods[$row->payment_method] ?? $row->payment_method }}@endif@if($row->bank) · {{ $row->bank->name }}@endif
                                    <span @class(['lb-badge', 'is-pending' => $row->status === 'pending', 'is-paid' => $row->status === 'paid'])>{{ $statuses[$row->status] ?? $row->status }}</span>
                                </span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ \App\Support\Money::format($row->amount_usd, 'USD') }}
                                <small>{{ \App\Support\Money::format($row->amount, $row->currency) }}</small>
                            </span>
                        </button>
                    @else
                        <div class="lb-entry" wire:key="expense-{{ $row->id }}">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">{{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}@if($row->payment_method) · {{ $methods[$row->payment_method] ?? $row->payment_method }}@endif@if($row->bank) · {{ $row->bank->name }}@endif</span>
                            </span>
                            <span class="lb-entry-amt">{{ \App\Support\Money::format($row->amount_usd, 'USD') }}</span>
                        </div>
                    @endcan
                @endforeach
            </div>
        @endif
    </section>

    @if($showModal)
        @teleport('body')
        <div class="lb-overlay" wire:click.self="closeModal">
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="expense-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <h2 id="expense-dialog-title">{{ $editingId ? 'Editar egreso' : 'Nuevo egreso' }}</h2>
                    <button type="button" wire:click="closeModal" class="lb-textbtn">Cerrar</button>
                </div>
                <div class="lb-form">
                    <label class="lb-field">
                        <span class="lb-label">Concepto</span>
                        <input wire:model="concept" type="text" class="lb-control" autofocus>
                        @error('concept') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <div class="lb-split">
                        <label class="lb-field">
                            <span class="lb-label">Monto</span>
                            <input wire:model.live="amount" type="number" min="0.01" step="0.01" inputmode="decimal" class="lb-control lb-money">
                            @error('amount') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="lb-field">
                            <span class="lb-label">Moneda</span>
                            <select wire:model.live="currency" class="lb-control">
                                @foreach($currencies as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="lb-split">
                        <label class="lb-field">
                            <span class="lb-label">Categoría</span>
                            <select wire:model="category" class="lb-control">
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="lb-field">
                            <span class="lb-label">Fecha</span>
                            <input wire:model.live="occurred_on" type="date" class="lb-control">
                            @error('occurred_on') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    <div class="lb-split">
                        <label class="lb-field">
                            <span class="lb-label">Estado</span>
                            <select wire:model="status" class="lb-control">
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="lb-field">
                            <span class="lb-label">Medio de pago</span>
                            <select wire:model.live="payment_method" class="lb-control">
                                <option value="">Sin indicar</option>
                                @foreach($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @if($asksForBank)
                        @include('livewire.partials.bank-field', ['label' => $bankLabel, 'banks' => $banks])
                    @endif
                    <label class="lb-field">
                        <span class="lb-label">Nota</span>
                        <textarea wire:model="notes" rows="2" class="lb-control"></textarea>
                    </label>
                    @include('livewire.partials.rate-note')
                </div>
                <div class="lb-dialog-foot">
                    @error('removal')
                        <p class="lb-error">{{ $message }}</p>
                    @enderror
                    @if($confirmingRemoval)
                        <p class="lb-dialog-ask">¿Borrar este egreso?</p>
                        <button type="button" wire:click="cancelRemoval" class="lb-btn lb-btn-ghost">No</button>
                        <button type="button" wire:click="delete({{ $editingId }})" class="lb-btn lb-btn-danger-solid" wire:loading.attr="disabled" wire:target="delete">Borrar</button>
                    @else
                        @if($editingId)
                            @can('egresos.delete')
                                <button type="button" wire:click="askRemoval" class="lb-textbtn is-danger">Borrar</button>
                            @endcan
                        @endif
                        <button type="button" wire:click="closeModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                        <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Guardar</span>
                            <span wire:loading wire:target="save">Guardando…</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
        @endteleport
    @endif
</div>
