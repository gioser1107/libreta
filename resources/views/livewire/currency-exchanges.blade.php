<section id="divisas">
    <div class="lb-block-head">
        <h2 class="lb-section-title">Divisas</h2>
        <button type="button" wire:click="openPlaceModal" class="lb-textbtn">Agregar</button>
    </div>

    @if($holdings !== [])
        <section class="lb-metrics" aria-label="Dólares fuera del banco">
            @foreach($holdings as $holding)
                <button type="button" wire:key="holding-{{ $holding['id'] }}" wire:click="editPlace({{ $holding['id'] }})" class="lb-metric">
                    <span>{{ $holding['label'] }}</span>
                    <strong>{{ \App\Support\Money::format($holding['balances']['USD'], 'USD') }}</strong>
                    @if($holding['balances']['EUR'] != 0)
                        <em>{{ \App\Support\Money::format($holding['balances']['EUR'], 'EUR') }}</em>
                    @endif
                </button>
            @endforeach
        </section>
    @endif

    @if($spreadUsd != 0)
        <p class="lb-note">
            @if($spreadUsd > 0)
                Al cambiar de BCV a tu tasa llevas perdido {{ \App\Support\Money::format($spreadUsd, 'USD') }}.
            @else
                Al cambiar de BCV a tu tasa llevas ganado {{ \App\Support\Money::format(abs($spreadUsd), 'USD') }}.
            @endif
        </p>
    @endif

    <div class="lb-panel">
        @if($holdings === [])
            <div class="lb-empty">
                <p>Agrega el lugar donde guardas las divisas. Puede llamarse como quieras.</p>
                <button type="button" wire:click="openPlaceModal" class="lb-btn lb-btn-primary">Agregar</button>
            </div>
        @elseif($exchanges->isEmpty())
            <div class="lb-empty">
                <p>Cuando cambias bolívares por dólares, anótalo aquí. No es un gasto: ves cuánto se fue entre el BCV y la tasa que pagaste.</p>
                <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Cambiar</button>
            </div>
        @else
            <div class="lb-list">
                @foreach($exchanges as $exchange)
                    <div wire:key="exchange-{{ $exchange->id }}" class="lb-entry">
                        @if($confirmingRemovalId === $exchange->id)
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">¿Borrar este cambio?</span>
                            </span>
                            <span class="lb-row-actions">
                                <button type="button" wire:click="cancelRemoval" class="lb-textbtn">No</button>
                                <button type="button" wire:click="delete({{ $exchange->id }})" class="lb-textbtn is-danger">Borrar</button>
                            </span>
                        @else
                            <span class="lb-entry-main">
                                <span class="lb-entry-title">{{ $exchange->fromBank?->name }} → {{ $exchange->placeName() }}</span>
                                <span class="lb-entry-meta">{{ $exchange->occurred_on->format('d/m/Y') }} · {{ $exchange->spreadSentence() }}</span>
                            </span>
                            <span class="lb-entry-amt">
                                {{ $exchange->legsLabel() }}
                            </span>
                            <button type="button" wire:click="askRemoval({{ $exchange->id }})" class="lb-textbtn is-danger">Borrar</button>
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
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="exchange-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <x-back wire:click="closeModal" />
                    <h2 id="exchange-dialog-title">Cambiar a divisas</h2>
                </div>
                <div class="lb-form">
                    <label class="lb-field">
                        <span class="lb-label">Banco</span>
                        <select wire:model="from_bank_id" class="lb-control">
                            <option value="">Elige un banco</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        @error('from_bank_id') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="lb-field">
                        <span class="lb-label">Dónde quedaron</span>
                        <select wire:model="divisa_place_id" class="lb-control">
                            <option value="">Elige</option>
                            @foreach($holdings as $holding)
                                <option value="{{ $holding['id'] }}">{{ $holding['label'] }}</option>
                            @endforeach
                        </select>
                        @error('divisa_place_id') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="lb-field">
                        <span class="lb-label">Fecha</span>
                        <input wire:model.live="occurred_on" type="date" class="lb-control">
                        @error('occurred_on') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <div class="lb-split">
                        <label class="lb-field">
                            <span class="lb-label">Pagué</span>
                            <select wire:model.live="paid_currency" class="lb-control">
                                @foreach($currencies as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('paid_currency') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="lb-field">
                            <span class="lb-label">Monto</span>
                            <input wire:model.live="paid_amount" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                            @error('paid_amount') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    <div class="lb-split">
                        <label class="lb-field">
                            <span class="lb-label">Recibí</span>
                            <select wire:model.live="received_currency" class="lb-control">
                                @foreach($currencies as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('received_currency') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="lb-field">
                            <span class="lb-label">Monto</span>
                            <input wire:model.live="received_amount" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                            @error('received_amount') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    @if($preview)
                        <p class="lb-note">
                            @if($preview['spread'] > 0)
                                Pierdes {{ \App\Support\Money::format($preview['spread'], 'USD') }} respecto al BCV ({{ number_format($preview['bcv'], 2, ',', '.') }}).
                            @elseif($preview['spread'] < 0)
                                Ganas {{ \App\Support\Money::format(abs($preview['spread']), 'USD') }} respecto al BCV ({{ number_format($preview['bcv'], 2, ',', '.') }}).
                            @else
                                Queda igual al BCV ({{ number_format($preview['bcv'], 2, ',', '.') }}).
                            @endif
                        </p>
                    @endif
                </div>
                <div class="lb-dialog-foot">
                    <button type="button" wire:click="closeModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                    <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Anotar</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
        @endteleport
    @endif

    @if($showPlaceModal)
        @teleport('body')
        <div class="lb-overlay" wire:click.self="closePlaceModal">
            <form wire:submit="savePlace" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="place-dialog-title" wire:keydown.escape="closePlaceModal">
                <div class="lb-dialog-head">
                    <x-back wire:click="closePlaceModal" />
                    <h2 id="place-dialog-title">{{ $editingPlaceId ? 'Editar lugar' : 'Nuevo lugar' }}</h2>
                </div>
                <div class="lb-form">
                    <label class="lb-field">
                        <span class="lb-label">Nombre</span>
                        <input wire:model="placeName" type="text" maxlength="60" class="lb-control" placeholder="Binance, efectivo…" autofocus>
                        @error('name') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="lb-dialog-foot">
                    @if($confirmingPlaceRemovalId)
                        <p class="lb-dialog-ask">¿Borrar este lugar?</p>
                        <button type="button" wire:click="cancelPlaceRemoval" class="lb-btn lb-btn-ghost">No</button>
                        <button type="button" wire:click="deletePlace({{ $confirmingPlaceRemovalId }})" class="lb-btn lb-btn-danger-solid">Borrar</button>
                    @else
                        @if($editingPlaceId)
                            <button type="button" wire:click="askPlaceRemoval({{ $editingPlaceId }})" class="lb-textbtn is-danger">Borrar</button>
                        @endif
                        <button type="button" wire:click="closePlaceModal" class="lb-btn lb-btn-ghost">Cancelar</button>
                        <button type="submit" class="lb-btn lb-btn-primary" wire:loading.attr="disabled" wire:target="savePlace">
                            <span wire:loading.remove wire:target="savePlace">{{ $editingPlaceId ? 'Guardar' : 'Agregar' }}</span>
                            <span wire:loading wire:target="savePlace">Guardando…</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
        @endteleport
    @endif
</section>
