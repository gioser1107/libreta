<div class="lb-page">
    <header class="lb-head lb-head-period">
        <div class="lb-head-row">
            <h1>Egresos</h1>
            @can('egresos.create')
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
            @endcan
        </div>
        @include('livewire.partials.month-switcher')
    </header>

    <p class="lb-total-line">
        <strong>{{ \App\Support\Money::format($monthUsd, 'USD') }}</strong>
        <span>{{ \App\Support\Money::format($monthVes, 'VES') }}</span>
        @if($filterStatus !== 'all')
            <button type="button" wire:click="$set('filterStatus', 'all')" class="lb-textbtn">Ver todos</button>
        @elseif($pendingUsd > 0)
            <button type="button" wire:click="$set('filterStatus', 'pending')" class="lb-textbtn">Por pagar {{ \App\Support\Money::format($pendingUsd, 'USD') }}</button>
        @endif
    </p>

    <div class="lb-filters">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar en todos los meses" class="lb-control grow" aria-label="Buscar en todos los meses">
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
                    <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
                @endcan
            </div>
        @else
            <div class="lb-list">
                @foreach($rows as $row)
                    @can('egresos.edit')
                        <button type="button" wire:click="edit({{ $row->id }})" wire:key="expense-{{ $row->id }}" class="lb-entry">
                            <x-move-mark kind="out" />
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">
                                    {{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}@if($row->bank) · {{ $row->bank->name }}@endif
                                    @if($row->status === 'pending')
                                        <span class="lb-badge is-pending">{{ $statuses[$row->status] }}</span>
                                    @endif
                                </span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ \App\Support\Money::format($row->amount_usd, 'USD') }}
                                <small>{{ \App\Support\Money::format($row->amount, $row->currency) }}</small>
                            </span>
                        </button>
                    @else
                        <div class="lb-entry" wire:key="expense-{{ $row->id }}">
                            <x-move-mark kind="out" />
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">{{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}@if($row->bank) · {{ $row->bank->name }}@endif
                                    @if($row->status === 'pending')
                                        <span class="lb-badge is-pending">{{ $statuses[$row->status] }}</span>
                                    @endif
                                </span>
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
