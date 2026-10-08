<div
    class="lb-page lb-home lb-calc"
    wire:ignore
    x-data="latasaCalculator(@js($quote))"
    x-on:calculator-rates-refreshed.window="applyQuote($event.detail.quote)"
    x-on:keydown.escape.window="menuOpen = false"
>
    <header class="lb-head">
        <div>
            <h1>Calculadora</h1>
            <p class="lb-help">Convierte con la tasa del día.</p>
        </div>
        <div class="lb-calc-tools">
            <button type="button" class="lb-icon-btn" title="Actualizar tasas" x-on:click="refresh()" x-bind:disabled="loading">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" x-bind:class="loading && 'is-spin'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 12a9 9 0 11-2.2-5.8M21 3v6h-6"/></svg>
            </button>
        </div>
    </header>

    <div class="lb-calc-grid">
    <div class="lb-calc-col">
    <section class="lb-hero" aria-label="Tasa del día">
        <p class="lb-hero-kicker" x-text="code.startsWith('USDT') ? meta.short : meta.name">Dólar</p>
        <p class="lb-figure-row" x-show="rate" @unless($quote['available']) x-cloak @endunless>
            <span class="lb-hero-amount" x-text="heroAmount">{{ $hero }}</span>
        </p>
        <p class="lb-hero-ves" x-show="rate" @unless($quote['available']) x-cloak @endunless>bolívares</p>
        <p class="lb-hero-note" x-show="!rate" x-text="error || 'Tasa no disponible actualmente'" @if($quote['available']) x-cloak @endif>{{ $quote['error'] }}</p>
        <p class="lb-hero-note" x-show="dateLabel" @unless($quote['labels']['USD']) x-cloak @endunless>
            <time x-text="dateLabel">{{ $quote['labels']['USD'] }}</time>
        </p>
        <div class="lb-hero-actions" x-show="rate" @unless($quote['available']) x-cloak @endunless>
            <button type="button" x-on:click="copyValue('rate')">
                <span x-text="copied === 'rate' ? 'Copiado' : 'Copiar'">Copiar</span>
            </button>
            <button type="button" x-on:click="share()">Compartir</button>
        </div>
    </section>

    <div class="lb-rate-grid" role="listbox" aria-label="Tipo de tasa">
        <button type="button" class="lb-rate is-active" role="option" x-bind:class="{ 'is-active': code === 'USD' }" x-bind:aria-selected="code === 'USD'" x-on:click="select('USD')">
            <strong>USD</strong>
            <small>BCV</small>
        </button>
        <button type="button" class="lb-rate" role="option" x-bind:class="{ 'is-active': code === 'EUR' }" x-bind:aria-selected="code === 'EUR'" x-on:click="select('EUR')">
            <strong>EUR</strong>
            <small>BCV</small>
        </button>
        <button type="button" class="lb-rate" role="option" x-bind:class="{ 'is-active': code === 'USDT_BUY' }" x-bind:aria-selected="code === 'USDT_BUY'" x-on:click="select('USDT_BUY')">
            <strong>USDT compra</strong>
            <small>Binance P2P</small>
        </button>
        <button type="button" class="lb-rate" role="option" x-bind:class="{ 'is-active': code === 'USDT_SELL' }" x-bind:aria-selected="code === 'USDT_SELL'" x-on:click="select('USDT_SELL')">
            <strong>USDT venta</strong>
            <small>Binance P2P</small>
        </button>
    </div>
    </div>

    <div class="lb-calc-col">
    <section class="lb-convert" aria-label="Conversión" x-show="rate" @unless($quote['available']) x-cloak @endunless>
        <div class="lb-convert-row" x-bind:class="animating && 'is-out'" x-on:click="$refs.topAmount.focus()">
            <span class="lb-convert-code" x-text="top.code">USD</span>
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
            <button type="button" class="lb-convert-copy" title="Copiar" x-on:click.stop="copyValue('top')" x-bind:class="copied === 'top' && 'is-done'">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
        </div>

        <button type="button" class="lb-btn lb-btn-ghost lb-convert-swap" x-on:click="swap()" x-bind:class="animating && 'is-turning'" aria-label="Invertir monedas">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4M7 4L3 8M7 4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
        </button>

        <div class="lb-convert-row" x-bind:class="animating && 'is-out'" x-on:click="$refs.bottomAmount.focus()">
            <span class="lb-convert-code" x-text="bottom.code">VES</span>
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
            <button type="button" class="lb-convert-copy" title="Copiar" x-on:click.stop="copyValue('bottom')" x-bind:class="copied === 'bottom' && 'is-done'">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
        </div>

        <button type="button" class="lb-btn lb-btn-ghost lb-btn-block" x-on:click="reset()">Restablecer</button>
    </section>

    <p class="lb-help" x-show="!rate" x-cloak x-text="error || 'Tasa no disponible'"></p>

    <div class="lb-gap" x-show="buyGap || sellGap" @unless($gaps['buy'] || $gaps['sell']) x-cloak @endunless>
        <p>Brecha BCV vs USDT</p>
        <div>
            <span x-show="buyGap">Compra <strong x-text="buyGap?.text">{{ $gaps['buy']['text'] ?? '' }}</strong></span>
            <span x-show="sellGap">Venta <strong x-text="sellGap?.text">{{ $gaps['sell']['text'] ?? '' }}</strong></span>
        </div>
    </div>

    <p class="lb-help lb-calc-note" x-text="infoText">Fecha de vigencia de la tasa del Banco Central de Venezuela (BCV).</p>
    </div>
    </div>
</div>
