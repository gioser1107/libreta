<div class="lb-page">
    <header class="lb-head">
        <div>
            <h1>Egresos</h1>
            @include('livewire.partials.month-switcher')
        </div>
        @can('egresos.create')
            <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo egreso</button>
        @endcan
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
                <p>No hay egresos en {{ mb_strtolower($months[$month] ?? 'este mes') }} {{ $year }}.</p>
                @can('egresos.create')
                    <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo egreso</button>
                @endcan
            </div>
        @else
            <div class="lb-list">
                @foreach($rows as $row)
                    @can('egresos.edit')
                        <button type="button" wire:click="edit({{ $row->id }})" class="lb-entry">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">
                                    {{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}
                                    <span @class(['lb-badge', 'is-pending' => $row->status === 'pending', 'is-paid' => $row->status === 'paid'])>{{ $statuses[$row->status] ?? $row->status }}</span>
                                </span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ \App\Support\Money::format($row->amount_usd, 'USD') }}
                                <small>{{ \App\Support\Money::format($row->amount, $row->currency) }}</small>
                            </span>
                        </button>
                    @else
                        <div class="lb-entry">
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $row->concept }}</span>
                                <span class="lb-entry-meta">{{ $row->occurred_on->format('d/m/Y') }} · {{ $categories[$row->category] ?? $row->category }}</span>
                            </span>
                            <span class="lb-entry-amt">{{ \App\Support\Money::format($row->amount_usd, 'USD') }}</span>
                        </div>
                    @endcan
                @endforeach
            </div>

            <div class="lb-table-wrap">
                <table class="lb-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th class="lb-num">Monto</th>
                            <th class="lb-num">En dólares</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td class="lb-muted">{{ $row->occurred_on->format('d/m/Y') }}</td>
                                <td>{{ $row->concept }}</td>
                                <td class="lb-muted">{{ $categories[$row->category] ?? $row->category }}</td>
                                <td>
                                    <span @class(['lb-badge', 'is-pending' => $row->status === 'pending', 'is-paid' => $row->status === 'paid'])>{{ $statuses[$row->status] ?? $row->status }}</span>
                                </td>
                                <td class="lb-num">{{ \App\Support\Money::format($row->amount, $row->currency) }}</td>
                                <td class="lb-num">{{ \App\Support\Money::format($row->amount_usd, 'USD') }}</td>
                                <td>
                                    <div class="lb-row-actions">
                                        @can('egresos.edit')
                                            <button type="button" wire:click="edit({{ $row->id }})" class="lb-textbtn">Editar</button>
                                        @endcan
                                        @can('egresos.delete')
                                            <button type="button" wire:click="delete({{ $row->id }})" wire:confirm="¿Borrar este egreso?" class="lb-textbtn is-danger">Borrar</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="lb-pager">{{ $rows->links() }}</div>
            @endif
        @endif
    </section>

    @if($showModal)
        <div class="lb-overlay" wire:click.self="$set('showModal', false)">
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="expense-dialog-title" wire:keydown.escape="$set('showModal', false)">
                <div class="lb-dialog-head">
                    <h2 id="expense-dialog-title">{{ $editingId ? 'Editar egreso' : 'Nuevo egreso' }}</h2>
                    <button type="button" wire:click="$set('showModal', false)" class="lb-textbtn">Cerrar</button>
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
                            <select wire:model="payment_method" class="lb-control">
                                <option value="">Sin indicar</option>
                                @foreach($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="lb-field">
                        <span class="lb-label">Nota</span>
                        <textarea wire:model="notes" rows="2" class="lb-control"></textarea>
                    </label>
                    @include('livewire.partials.rate-note')
                    @if($editingId)
                        @can('egresos.delete')
                            <button type="button" wire:click="delete({{ $editingId }})" wire:confirm="¿Borrar este egreso?" class="lb-textbtn is-danger">Borrar este egreso</button>
                        @endcan
                    @endif
                </div>
                <div class="lb-dialog-foot">
                    <button type="button" wire:click="$set('showModal', false)" class="lb-btn lb-btn-ghost">Cancelar</button>
                    <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Guardar</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
