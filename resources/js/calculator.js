const CURRENCIES = {
    USD: {
        name: 'Dólar',
        label: 'DÓLAR',
        source: 'BCV',
        short: 'USD',
        flag: 'https://flagcdn.com/w80/us.png',
        flagAlt: 'Bandera de Estados Unidos',
    },
    EUR: {
        name: 'Euro',
        label: 'EURO',
        source: 'BCV',
        short: 'EUR',
        flag: 'https://flagcdn.com/w80/eu.png',
        flagAlt: 'Bandera de la Unión Europea',
    },
    USDT_BUY: {
        name: 'Tether',
        label: 'USDT COMPRA',
        source: 'P2P BINANCE',
        short: 'USDT Compra',
        code: 'USDT',
        flag: 'https://public.bnbstatic.com/static/images/common/favicon.ico',
        flagAlt: 'Binance',
        dot: '#22C55E',
    },
    USDT_SELL: {
        name: 'Tether',
        label: 'USDT VENTA',
        source: 'P2P BINANCE',
        short: 'USDT Venta',
        code: 'USDT',
        flag: 'https://public.bnbstatic.com/static/images/common/favicon.ico',
        flagAlt: 'Binance',
        dot: '#EF4444',
    },
};

const VES = {
    code: 'VES',
    flag: 'https://flagcdn.com/w80/ve.png',
    flagAlt: 'Bandera de Venezuela',
};

function parseAmount(value) {
    const numeric = String(value ?? '').replace(/[,\s]/g, '');
    const amount = Number.parseFloat(numeric);

    return Number.isNaN(amount) ? null : amount;
}

function formatEn(value, digits = 2) {
    const amount = typeof value === 'string' ? Number.parseFloat(value) : value;

    if (Number.isNaN(amount)) {
        return '';
    }

    return amount.toLocaleString('en-US', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });
}

function formatVe(value) {
    return value.toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function maskAmount(raw) {
    const cleaned = String(raw).replace(/[^\d.,]/g, '');

    if (! cleaned) {
        return '';
    }

    const decimal = cleaned.match(/[.,](\d{0,2})$/);

    if (decimal !== null && (decimal[1].length !== 3 || cleaned.endsWith('.') || cleaned.endsWith(','))) {
        const splitAt = Math.max(cleaned.lastIndexOf('.'), cleaned.lastIndexOf(','));
        const whole = cleaned.slice(0, splitAt).replace(/[.,]/g, '').replace(/^0+/, '') || '0';
        const fraction = cleaned.slice(splitAt + 1).replace(/[.,]/g, '').slice(0, 2);

        return `${whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}.${fraction}`;
    }

    const whole = cleaned.replace(/[.,]/g, '').replace(/^0+/, '') || '0';

    return whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

document.addEventListener('alpine:init', () => {
    Alpine.data('latasaCalculator', (quote) => ({
        rates: quote.rates,
        labels: quote.labels,
        error: quote.error,
        code: 'USD',
        menuOpen: false,
        infoOpen: false,
        swapped: false,
        animating: false,
        foreignDraft: '',
        vesDraft: '',
        foreignBase: '1.00',
        vesBase: '0.00',
        copied: null,
        loading: false,
        copyTimer: null,

        init() {
            this.recomputeVes();
        },

        get meta() {
            return CURRENCIES[this.code];
        },

        get rate() {
            const value = this.rates?.[this.code];

            return typeof value === 'number' && value > 0 ? value : null;
        },

        get heroAmount() {
            return this.rate ? formatVe(this.rate) : '';
        },

        get dateLabel() {
            return this.labels?.[this.code] ?? '';
        },

        get infoText() {
            if (this.code === 'USDT_BUY') {
                return 'Tasa para comprar USDT en Binance P2P (pagas este precio por cada USDT).';
            }

            if (this.code === 'USDT_SELL') {
                return 'Tasa para vender USDT en Binance P2P (recibes este precio por cada USDT).';
            }

            return 'Fecha de vigencia de la tasa del Banco Central de Venezuela (BCV).';
        },

        get buyGap() {
            return this.gap(this.rates?.USDT_BUY);
        },

        get sellGap() {
            return this.gap(this.rates?.USDT_SELL);
        },

        get top() {
            return this.swapped ? this.vesField() : this.foreignField();
        },

        get bottom() {
            return this.swapped ? this.foreignField() : this.vesField();
        },

        foreignField() {
            return {
                key: 'foreign',
                code: this.meta.code ?? this.code,
                flag: this.meta.flag,
                flagAlt: this.meta.flagAlt,
                value: this.foreignDraft,
                placeholder: this.foreignBase,
            };
        },

        vesField() {
            return {
                key: 'ves',
                code: VES.code,
                flag: VES.flag,
                flagAlt: VES.flagAlt,
                value: this.vesDraft,
                placeholder: this.vesBase,
            };
        },

        gap(usdt) {
            const usd = this.rates?.USD;

            if (typeof usdt !== 'number' || typeof usd !== 'number' || usd <= 0) {
                return null;
            }

            const percent = ((usdt - usd) / usd) * 100;

            return {
                text: `${percent > 0 ? '+' : ''}${percent.toFixed(2)}%`,
                tone: percent > 0 ? 'up' : (percent < 0 ? 'down' : 'flat'),
            };
        },

        recomputeVes() {
            const amount = parseAmount(this.foreignDraft || this.foreignBase) ?? 1;

            this.vesBase = this.rate ? formatEn(amount * this.rate) : '0.00';
        },

        onForeign(value) {
            this.foreignDraft = maskAmount(value);

            if (! this.foreignDraft) {
                this.foreignBase = '1.00';
                this.vesDraft = '';
                this.recomputeVes();

                return;
            }

            const amount = parseAmount(this.foreignDraft);

            if (amount !== null && this.rate) {
                this.vesBase = formatEn(amount * this.rate);
            }
        },

        onVes(value) {
            this.vesDraft = maskAmount(value);

            if (! this.vesDraft) {
                this.foreignBase = '1.00';
                this.foreignDraft = '';
                this.recomputeVes();

                return;
            }

            const amount = parseAmount(this.vesDraft);

            if (amount !== null && this.rate) {
                this.foreignBase = formatEn(amount / this.rate);
            }
        },

        onField(key, value) {
            if (key === 'ves') {
                this.onVes(value);

                return;
            }

            this.onForeign(value);
        },

        select(code) {
            this.code = code;
            this.menuOpen = false;

            if (this.foreignDraft) {
                this.onForeign(this.foreignDraft);
            } else if (this.vesDraft) {
                this.onVes(this.vesDraft);
            } else {
                this.recomputeVes();
            }
        },

        swap() {
            if (this.animating || ! this.rate) {
                return;
            }

            this.animating = true;
            window.setTimeout(() => {
                this.swapped = ! this.swapped;
                this.animating = false;
            }, 200);
        },

        reset() {
            this.foreignDraft = '';
            this.vesDraft = '';
            this.foreignBase = '1.00';
            this.swapped = false;
            this.recomputeVes();
        },

        async copyValue(key) {
            const field = key === 'top' ? this.top : (key === 'bottom' ? this.bottom : null);
            const raw = key === 'rate'
                ? this.rate?.toFixed(2)
                : (field?.value || field?.placeholder || '');
            const amount = parseAmount(raw);

            if (amount === null) {
                return;
            }

            await this.writeClipboard(amount.toFixed(2), key);
        },

        async share() {
            if (! this.rate) {
                return;
            }

            const text = `💵 Tasa BCV - El ${this.meta.name} está en ${this.heroAmount} bolívares`;

            if (typeof navigator.share === 'function') {
                try {
                    await navigator.share({ title: 'Tasa BCV', text, url: window.location.href });
                } catch {
                    // El usuario canceló el cuadro de compartir.
                }

                return;
            }

            await this.writeClipboard(text, 'share');
        },

        async writeClipboard(text, key) {
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.style.position = 'fixed';
                    area.style.left = '-9999px';
                    document.body.appendChild(area);
                    area.select();
                    document.execCommand('copy');
                    area.remove();
                }

                this.copied = key;
                window.clearTimeout(this.copyTimer);
                this.copyTimer = window.setTimeout(() => {
                    this.copied = null;
                }, 2000);
            } catch {
                this.copied = null;
            }
        },

        async refresh() {
            this.loading = true;

            try {
                await this.$wire.refreshRates();
            } finally {
                this.loading = false;
            }
        },

        applyQuote(next) {
            if (! next?.rates) {
                return;
            }

            this.rates = next.rates;
            this.labels = next.labels;
            this.error = next.error;

            if (this.foreignDraft) {
                this.onForeign(this.foreignDraft);
            } else if (this.vesDraft) {
                this.onVes(this.vesDraft);
            } else {
                this.recomputeVes();
            }
        },
    }));
});
