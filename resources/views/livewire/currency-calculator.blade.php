<div>
    <div
        class="lt"
        wire:ignore
        x-data="latasaCalculator(@js($quote))"
        x-on:calculator-rates-refreshed.window="applyQuote($event.detail.quote)"
        x-on:keydown.escape.window="menuOpen = false; infoOpen = false"
    >
        <section class="lt-hero" aria-label="Tasa del día">
            <p class="lt-kicker">
                <span>EL</span>
                <strong x-text="meta.label">DÓLAR</strong>
                <span>ESTÁ EN</span>
            </p>

            <div class="lt-figure">
                <p class="lt-figure-row" x-show="rate" @unless($quote['available']) x-cloak @endunless>
                    <span class="lt-amount" x-text="heroAmount">{{ $hero }}</span>
                    <span class="lt-unit">bolívares</span>
                </p>
                <p class="lt-missing" x-show="!rate" x-text="error || 'Tasa no disponible actualmente'" @if($quote['available']) x-cloak @endif>{{ $quote['error'] }}</p>
            </div>

            <div class="lt-tools">
                <div class="lt-picker" x-on:click.outside="menuOpen = false">
                    <button type="button" class="lt-pill" x-on:click="menuOpen = !menuOpen" x-bind:aria-expanded="menuOpen" aria-haspopup="listbox">
                        <span class="lt-pill-mark" x-show="!code.startsWith('USDT')">BCV</span>
                        <span class="lt-pill-logo" x-show="code.startsWith('USDT')" x-cloak>
                            <img src="https://public.bnbstatic.com/static/images/common/favicon.ico" alt="" width="24" height="24">
                            <i x-bind:style="`background:${meta.dot}`"></i>
                        </span>
                        <span x-text="meta.short">USD</span>
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" x-bind:class="menuOpen && 'is-open'"><path d="M6 9l6 6 6-6"/></svg>
                    </button>

                    <div class="lt-menu" x-show="menuOpen" x-cloak role="listbox">
                        <template x-for="(item, key) in {
                            USD: { short: 'USD', source: 'BCV', flag: 'https://flagcdn.com/w80/us.png', alt: 'Bandera de Estados Unidos' },
                            EUR: { short: 'EUR', source: 'BCV', flag: 'https://flagcdn.com/w80/eu.png', alt: 'Bandera de la Unión Europea' },
                            USDT_BUY: { short: 'USDT Compra', source: 'P2P BINANCE', flag: 'https://public.bnbstatic.com/static/images/common/favicon.ico', alt: 'Binance', dot: '#22C55E' },
                            USDT_SELL: { short: 'USDT Venta', source: 'P2P BINANCE', flag: 'https://public.bnbstatic.com/static/images/common/favicon.ico', alt: 'Binance', dot: '#EF4444' }
                        }" :key="key">
                            <button type="button" role="option" x-bind:aria-selected="code === key" x-bind:class="code === key && 'is-active'" x-on:click="select(key)">
                                <span class="lt-menu-flag">
                                    <img x-bind:src="item.flag" x-bind:alt="item.alt" width="24" height="24">
                                    <i x-show="item.dot" x-bind:style="`background:${item.dot}`"></i>
                                </span>
                                <span>
                                    <strong x-text="item.short"></strong>
                                    <small x-text="item.source"></small>
                                </span>
                                <svg x-show="code === key" width="14" height="14" fill="none" stroke="#22C55E" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <button type="button" class="lt-refresh" title="Actualizar tasas" x-on:click="refresh()" x-bind:disabled="loading">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true" x-bind:class="loading && 'is-spin'"><path d="M21 12a9 9 0 11-2.2-5.8M21 3v6h-6"/></svg>
                </button>
            </div>

            <div class="lt-updated" x-show="dateLabel" @unless($quote['labels']['USD']) x-cloak @endunless>
                <span>Última actualización:</span>
                <div>
                    <time x-text="dateLabel">{{ $quote['labels']['USD'] }}</time>
                    <span class="lt-info">
                        <button type="button" aria-label="Información de la tasa" x-on:click="infoOpen = !infoOpen" x-on:mouseenter="infoOpen = true" x-on:mouseleave="infoOpen = false">i</button>
                        <p x-show="infoOpen" x-cloak x-text="infoText"></p>
                    </span>
                </div>
            </div>

            <div class="lt-actions" x-show="rate" @unless($quote['available']) x-cloak @endunless>
                <button type="button" x-on:click="copyValue('rate')">
                    <svg x-show="copied !== 'rate'" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    <svg x-show="copied === 'rate'" x-cloak width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                    <span x-text="copied === 'rate' ? '¡Copiado!' : 'Copiar'">Copiar</span>
                </button>
                <button type="button" x-on:click="share()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                    Compartir
                </button>
            </div>
        </section>

        <section class="lt-sheet" aria-label="Calculadora">
            <div class="lt-sheet-inner">
                <h1>Calculadora</h1>

                <div class="lt-stack" x-show="rate" @unless($quote['available']) x-cloak @endunless>
                    <div class="lt-card" x-bind:class="animating && 'is-out'" x-on:click="$refs.topAmount.focus()">
                        <img x-bind:src="top.flag" x-bind:alt="top.flagAlt" src="https://flagcdn.com/w80/us.png" alt="Bandera de Estados Unidos" width="32" height="32">
                        <span class="lt-code" x-text="top.code">USD</span>
                        <input
                            x-ref="topAmount"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            x-bind:value="top.value"
                            x-bind:placeholder="top.placeholder"
                            placeholder="1.00"
                            x-on:input="onField(top.key, $event.target.value)"
                            aria-label="Monto superior"
                        >
                        <button type="button" class="lt-copy" title="Copiar" x-on:click.stop="copyValue('top')" x-bind:class="copied === 'top' && 'is-done'">
                            <svg x-show="copied !== 'top'" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            <svg x-show="copied === 'top'" x-cloak width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>

                    <button type="button" class="lt-swap" aria-label="Invertir monedas" x-on:click="swap()" x-bind:class="animating && 'is-turning'">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 16V4M7 4L3 8M7 4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </button>

                    <div class="lt-card" x-bind:class="animating && 'is-out'" x-on:click="$refs.bottomAmount.focus()">
                        <img x-bind:src="bottom.flag" x-bind:alt="bottom.flagAlt" src="https://flagcdn.com/w80/ve.png" alt="Bandera de Venezuela" width="32" height="32">
                        <span class="lt-code" x-text="bottom.code">VES</span>
                        <input
                            x-ref="bottomAmount"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            x-bind:value="bottom.value"
                            x-bind:placeholder="bottom.placeholder"
                            placeholder="{{ $vesPlaceholder }}"
                            x-on:input="onField(bottom.key, $event.target.value)"
                            aria-label="Monto inferior"
                        >
                        <button type="button" class="lt-copy" title="Copiar" x-on:click.stop="copyValue('bottom')" x-bind:class="copied === 'bottom' && 'is-done'">
                            <svg x-show="copied !== 'bottom'" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            <svg x-show="copied === 'bottom'" x-cloak width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>

                <p class="lt-missing lt-missing-sheet" x-show="!rate" x-cloak x-text="error || 'Tasa no disponible'"></p>

                <button type="button" class="lt-reset" x-show="rate" @unless($quote['available']) x-cloak @endunless x-on:click="reset()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    Restablecer
                </button>

                <div class="lt-gap" x-show="buyGap || sellGap" @unless($gaps['buy'] || $gaps['sell']) x-cloak @endunless>
                    <p>Brecha BCV vs USDT</p>
                    <div>
                        <span x-show="buyGap">Compra: <strong x-text="buyGap?.text" x-bind:class="buyGap && `is-${buyGap.tone}`" @class(['is-up' => ($gaps['buy']['tone'] ?? null) === 'up', 'is-down' => ($gaps['buy']['tone'] ?? null) === 'down'])>{{ $gaps['buy']['text'] ?? '' }}</strong></span>
                        <span x-show="sellGap">Venta: <strong x-text="sellGap?.text" x-bind:class="sellGap && `is-${sellGap.tone}`" @class(['is-up' => ($gaps['sell']['tone'] ?? null) === 'up', 'is-down' => ($gaps['sell']['tone'] ?? null) === 'down'])>{{ $gaps['sell']['text'] ?? '' }}</strong></span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
