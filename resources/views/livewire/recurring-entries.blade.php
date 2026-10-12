<div>
    <header class="lb-head">
        <div class="lb-head-row">
            <div>
                <x-back :href="route('profile')" />
                <h1>Cada mes</h1>
            </div>
            <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
        </div>
    </header>

    @if($incomeRows === [] && $expenseRows === [])
        <div class="lb-empty">
            <p>Nada se repite todavía.</p>
            <button type="button" wire:click="openModal" class="lb-btn lb-btn-primary">Nuevo</button>
        </div>
    @else
        <div class="lb-top">
            <section class="lb-hero" aria-label="Lo que queda cada mes">
                @if($baseline['ready'])
                    <p class="lb-hero-kicker">Te queda</p>
                    <p class="lb-hero-amount">{{ $baseline['leftover_usd'] < 0 ? '−' : '' }}{{ \App\Support\Money::format(abs($baseline['leftover_usd']), 'USD') }}</p>
                    @if($baseline['income_usd'] > 0 && $baseline['expense_usd'] > 0)
                        <div class="lb-splitbar" aria-hidden="true">
                            <span class="is-in" style="flex: {{ $baseline['income_usd'] }} 1 0"></span>
                            <span class="is-out" style="flex: {{ $baseline['expense_usd'] }} 1 0"></span>
                        </div>
                        <div class="lb-hero-legend">
                            <span>Ingresos {{ \App\Support\Money::format($baseline['income_usd'], 'USD') }}</span>
                            <span>Gastos {{ \App\Support\Money::format($baseline['expense_usd'], 'USD') }}</span>
                        </div>
                    @endif
                @else
                    <p class="lb-hero-kicker">Cada mes</p>
                @endif
                @if($baseline['note'])
                    <p class="lb-hero-note">{{ $baseline['note'] }}</p>
                @endif
            </section>

            <section class="lb-metrics" aria-label="Ingresos y gastos fijos">
                <div class="lb-metric">
                    <span>Ingresos</span>
                    <strong>@if($baseline['ready']){{ \App\Support\Money::format($baseline['income_usd'], 'USD') }}@else—@endif</strong>
                    <em>{{ $baseline['income_caption'] }}</em>
                </div>
                <div class="lb-metric">
                    <span>Gastos</span>
                    <strong>@if($baseline['ready']){{ \App\Support\Money::format($baseline['expense_usd'], 'USD') }}@else—@endif</strong>
                    <em>{{ $baseline['expense_caption'] }}</em>
                </div>
            </section>
        </div>

        <div class="lb-pair">
            <section class="lb-block" aria-label="Ingresos fijos">
                <div class="lb-block-head">
                    <h2 class="lb-section-title">Ingresos</h2>
                </div>
                <div class="lb-panel">
                    @if($incomeRows === [])
                        <p class="lb-empty">Nada entra fijo.</p>
                    @else
                        <div class="lb-list">
                            @foreach($incomeRows as $row)
                                @include('livewire.partials.recurring-row', ['row' => $row])
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="lb-block" aria-label="Gastos fijos">
                <div class="lb-block-head">
                    <h2 class="lb-section-title">Gastos</h2>
                </div>
                <div class="lb-panel">
                    @if($expenseRows === [])
                        <p class="lb-empty">Nada sale fijo.</p>
                    @else
                        <div class="lb-list">
                            @foreach($expenseRows as $row)
                                @include('livewire.partials.recurring-row', ['row' => $row])
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    @endif

    @if($showModal)
        @teleport('body')
        <div class="lb-overlay" wire:click.self="closeModal">
            <form wire:submit="save" class="lb-dialog" role="dialog" aria-modal="true" aria-labelledby="recurring-dialog-title" wire:keydown.escape="closeModal">
                <div class="lb-dialog-head">
                    <x-back wire:click="closeModal" />
                    <h2 id="recurring-dialog-title">{{ $editingId ? 'Editar fijo' : 'Nuevo fijo' }}</h2>
                </div>
                <div class="lb-form">
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
                        <input wire:model="concept" type="text" maxlength="160" class="lb-control" placeholder="Alquiler, sueldo, internet…" autofocus>
                        @error('concept') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <div class="lb-split">
                        <label class="lb-field">
                            <span class="lb-label">Monto</span>
                            <input wire:model="amount" type="text" inputmode="decimal" class="lb-control" placeholder="0">
                            @error('amount') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="lb-field">
                            <span class="lb-label">Moneda</span>
                            <select wire:model="currency" class="lb-control">
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
                            <span class="lb-label">Día del mes</span>
                            <input wire:model="day_of_month" type="number" min="1" max="31" class="lb-control">
                            @error('day_of_month') <span class="lb-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
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
                        <div class="lb-split">
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
                                <span class="lb-label">Al anotarlo</span>
                                <select wire:model="status" class="lb-control">
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    @endif
                    <label class="lb-field">
                        <span class="lb-label">Nota</span>
                        <textarea wire:model="notes" rows="2" class="lb-control"></textarea>
                        @error('notes') <span class="lb-error">{{ $message }}</span> @enderror
                    </label>
                    <p class="lb-note">Se anota solo, el día que elijas. El 31 cae el último día de los meses cortos.</p>
                </div>
                <div class="lb-dialog-foot">
                    @error('removal')
                        <p class="lb-error">{{ $message }}</p>
                    @enderror
                    @if($confirmingRemoval)
                        <p class="lb-dialog-ask">¿Quitar este fijo? Lo ya anotado se queda.</p>
                        <button type="button" wire:click="cancelRemoval" class="lb-btn lb-btn-ghost">No</button>
                        <button type="button" wire:click="delete" class="lb-btn lb-btn-danger-solid" wire:loading.attr="disabled" wire:target="delete">Quitar</button>
                    @else
                        @if($editingId)
                            <button type="button" wire:click="askRemoval" class="lb-textbtn is-danger">Quitar</button>
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
