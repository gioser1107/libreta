<div class="lb-page">
    <header class="lb-head">
        <div>
            <h1>Ingresos</h1>
            @include('livewire.partials.month-switcher')
        </div>
        @can('ingresos.create')
            <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
        @endcan
    </header>

    <p class="lb-total-line">
        <strong>{{ \App\Support\Money::format($monthUsd, 'USD') }}</strong>
        <span>{{ \App\Support\Money::format($monthVes, 'VES') }}</span>
    </p>

    <div class="lb-filters">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por concepto" class="lb-control grow" aria-label="Buscar por concepto">
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
                @if($search !== '' || $filterCategory !== 'all')
                    <p>Ningún ingreso coincide.</p>
                @else
                    <p>No hay ingresos en {{ mb_strtolower($months[$month] ?? 'este mes') }} {{ $year }}.</p>
                @endif
                @can('ingresos.create')
                    <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
                @endcan
            </div>
        @else
            <div class="lb-list">
                @foreach($rows as $row)
                    @can('ingresos.edit')
                        <button type="button" wire:click="edit({{ $row->id }})" wire:key="income-{{ $row->id }}" class="lb-entry">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">{{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}</span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ \App\Support\Money::format($row->amount_usd, 'USD') }}
                                <small>{{ \App\Support\Money::format($row->amount, $row->currency) }}</small>
                            </span>
                        </button>
                    @else
                        <div class="lb-entry" wire:key="income-{{ $row->id }}">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">{{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}</span>
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
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="income-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <h2 id="income-dialog-title">{{ $editingId ? 'Editar ingreso' : 'Nuevo ingreso' }}</h2>
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
                    <label class="lb-field">
                        <span class="lb-label">Nota</span>
                        <textarea wire:model="notes" rows="2" class="lb-control"></textarea>
                    </label>
                    @include('livewire.partials.rate-note')
                    @if($editingId)
                        @can('ingresos.delete')
                            <button type="button" wire:click="delete({{ $editingId }})" wire:confirm="¿Borrar este ingreso?" class="lb-textbtn is-danger">Borrar este ingreso</button>
                        @endcan
                    @endif
                </div>
                <div class="lb-dialog-foot">
                    <button type="button" wire:click="closeModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                    <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Guardar</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
        @endteleport
    @endif
</div>
