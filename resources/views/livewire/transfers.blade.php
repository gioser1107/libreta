<section id="traspasos" class="lb-panel lb-banks">
    <div class="lb-panel-head">
        <h2>Pasar entre bancos</h2>
    </div>

    @error('removal')
        <p class="lb-error lb-bank-error">{{ $message }}</p>
    @enderror

    @if($transfers->isEmpty())
        <p class="lb-empty">Todavía no has pasado plata entre tus bancos.</p>
    @else
        <div class="lb-list">
            @foreach($transfers as $transfer)
                <div wire:key="transfer-{{ $transfer->id }}" class="lb-entry">
                    @if($confirmingRemovalId === $transfer->id)
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">¿Borrar este traspaso?</span>
                        </span>
                        <span class="lb-row-actions">
                            <button type="button" wire:click="cancelRemoval" class="lb-textbtn">No</button>
                            <button type="button" wire:click="delete({{ $transfer->id }})" class="lb-textbtn is-danger">Borrar</button>
                        </span>
                    @else
                        <span class="lb-entry-main">
                            <span class="lb-entry-title">{{ $transfer->fromBank?->name }} → {{ $transfer->toBank?->name }}</span>
                            <span class="lb-entry-meta">{{ $transfer->occurred_on->format('d/m/Y') }}</span>
                        </span>
                        <span class="lb-entry-amt">{{ \App\Support\Money::format($transfer->amount, $transfer->currency) }}</span>
                        <button type="button" wire:click="askRemoval({{ $transfer->id }})" class="lb-textbtn is-danger">Borrar</button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <form wire:submit="save" class="lb-row-body">
        <label class="lb-field">
            <span class="lb-label">Desde</span>
            <select wire:model="from_bank_id" class="lb-control">
                <option value="">Elige un banco</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                @endforeach
            </select>
            @error('from_bank_id') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Hacia</span>
            <select wire:model="to_bank_id" class="lb-control">
                <option value="">Elige un banco</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                @endforeach
            </select>
            @error('to_bank_id') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Fecha</span>
            <input wire:model="occurred_on" type="date" class="lb-control">
            @error('occurred_on') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Moneda</span>
            <select wire:model="currency" class="lb-control">
                @foreach($currencies as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('currency') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <label class="lb-field">
            <span class="lb-label">Monto</span>
            <input wire:model="amount" type="text" inputmode="decimal" class="lb-control" placeholder="0">
            @error('amount') <span class="lb-error">{{ $message }}</span> @enderror
        </label>
        <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Pasar</span>
            <span wire:loading wire:target="save">Guardando…</span>
        </button>
    </form>
</section>
