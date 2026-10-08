<section class="lb-panel lb-banks">
    <div class="lb-panel-head">
        <h2>Cada mes</h2>
        <p>Sueldo, alquiler, internet</p>
    </div>

    @error('removal')
        <p class="lb-error lb-bank-error">{{ $message }}</p>
    @enderror

    @if($entries->isEmpty())
        <p class="lb-empty">Nada se repite todavía.</p>
    @else
        <div class="lb-list">
            @foreach($entries as $entry)
                <div wire:key="recurring-{{ $entry->id }}" class="lb-entry">
                    @if($confirmingRemovalId === $entry->id)
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">¿Quitar {{ $entry->concept }}?</span>
                            <span class="lb-entry-meta">Lo ya anotado se queda.</span>
                        </span>
                        <span class="lb-row-actions">
                            <button type="button" wire:click="cancelRemoval" class="lb-textbtn">No</button>
                            <button type="button" wire:click="delete({{ $entry->id }})" class="lb-textbtn is-danger">Quitar</button>
                        </span>
                    @else
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">{{ $entry->concept }}</span>
                            <span class="lb-entry-meta">
                                Día {{ $entry->day_of_month }} · {{ $kinds[$entry->kind] ?? $entry->kind }} · {{ $entry->categoryLabel() }}
                                @if($entry->bank) · {{ $entry->bank->name }} @endif
                            </span>
                        </span>
                        <span class="lb-entry-amt">{{ \App\Support\Money::format($entry->amount, $entry->currency) }}</span>
                        <span class="lb-row-actions">
                            <button type="button" wire:click="edit({{ $entry->id }})" class="lb-textbtn">Editar</button>
                            <button type="button" wire:click="askRemoval({{ $entry->id }})" class="lb-textbtn is-danger">Quitar</button>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <form wire:submit="save" class="lb-row-body">
        <label class="lb-field">
            <span class="lb-label">Tipo</span>
            <select wire:model.live="kind" class="lb-control">
                @foreach($kinds as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('kind') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Concepto</span>
            <input wire:model="concept" type="text" maxlength="160" class="lb-control" placeholder="Alquiler">
            @error('concept') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
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
            <span class="lb-label">Moneda</span>
            <select wire:model="currency" class="lb-control">
                @foreach($currencies as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="lb-field">
            <span class="lb-label">Monto</span>
            <input wire:model="amount" type="text" inputmode="decimal" class="lb-control" placeholder="0">
            @error('amount') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Día del mes</span>
            <input wire:model="day_of_month" type="number" min="1" max="31" class="lb-control">
            @error('day_of_month') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Banco</span>
            <select wire:model="bank_id" class="lb-control">
                <option value="">Sin banco</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                @endforeach
            </select>
            @error('bank_id') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        @if($kind === 'expense')
            <label class="lb-field">
                <span class="lb-label">Medio de pago</span>
                <select wire:model="payment_method" class="lb-control">
                    <option value="">Sin medio</option>
                    @foreach($methods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('payment_method') <span class="lb-error">{{ $message }}</span> @enderror
            </label>
            <label class="lb-field">
                <span class="lb-label">Estado</span>
                <select wire:model="status" class="lb-control">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <div class="lb-row-actions">
            @if($editingId)
                <button type="button" wire:click="cancelEdit" class="lb-btn lb-btn-ghost">Cancelar</button>
            @endif
            <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Guardar' : 'Agregar' }}</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>
    </form>
</section>
