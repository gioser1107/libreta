<section id="traspasos">
    <div class="lb-block-head">
        <h2 class="lb-section-title">Traspasos</h2>
    </div>

    <div class="lb-panel">
        @if($transfers->isEmpty())
            <div class="lb-empty">
                <p>Todavía no has pasado plata entre tus bancos.</p>
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Pasar</button>
            </div>
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
    </div>

    @error('removal')
        <p class="lb-error lb-bank-error">{{ $message }}</p>
    @enderror

    @if($showModal)
        @teleport('body')
        <div class="lb-overlay" wire:click.self="closeModal">
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="transfer-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <x-back wire:click="closeModal" />
                    <h2 id="transfer-dialog-title">Pasar entre bancos</h2>
                </div>
                <div class="lb-form">
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
                    <div class="lb-split">
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
                    </div>
                    <label class="lb-field">
                        <span class="lb-label">Monto</span>
                        <input wire:model="amount" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                        @error('amount') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="lb-dialog-foot">
                    <button type="button" wire:click="closeModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                    <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Pasar</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
        @endteleport
    @endif
</section>
