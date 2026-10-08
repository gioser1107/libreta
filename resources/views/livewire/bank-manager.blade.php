<section class="lb-panel lb-banks">
    <div class="lb-panel-head">
        <h2>Mis bancos</h2>
    </div>

    @error('removal')
        <p class="lb-error lb-bank-error">{{ $message }}</p>
    @enderror

    @if($banks->isEmpty())
        <p class="lb-empty">Todavía no tienes bancos.</p>
    @else
        <div class="lb-list">
            @foreach($banks as $bank)
                <div wire:key="bank-{{ $bank->id }}" class="lb-entry">
                    @if($confirmingRemovalId === $bank->id)
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">¿Borrar {{ $bank->name }}?</span>
                        </span>
                        <span class="lb-row-actions">
                            <button type="button" wire:click="cancelRemoval" class="lb-textbtn">No</button>
                            <button type="button" wire:click="delete({{ $bank->id }})" class="lb-textbtn is-danger">Borrar</button>
                        </span>
                    @else
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">{{ $bank->name }}</span>
                            <span class="lb-entry-meta">{{ $balances[$bank->id]['label'] ?? 'Sin saldo' }}</span>
                        </span>
                        <span class="lb-row-actions">
                            <button type="button" wire:click="edit({{ $bank->id }})" class="lb-textbtn">Editar</button>
                            <button type="button" wire:click="askRemoval({{ $bank->id }})" class="lb-textbtn is-danger">Borrar</button>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <form wire:submit="save" class="lb-row-body">
        <label class="lb-field">
            <span class="lb-label">{{ $editingId ? 'Nombre' : 'Nuevo banco' }}</span>
            <input wire:model="name" type="text" maxlength="60" class="lb-control" @unless($editingId) placeholder="Banesco, Mercantil…" @endunless>
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
