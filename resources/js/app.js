import './passkeys';
import './pwa';
import './page-loader';
import './calculator';

function preventPageZoom() {
    const blockGesture = (event) => {
        event.preventDefault();
    };

    for (const type of ['gesturestart', 'gesturechange', 'gestureend']) {
        document.addEventListener(type, blockGesture, { passive: false });
    }

    const blockPinch = (event) => {
        if (event.touches.length > 1) {
            event.preventDefault();
        }
    };

    document.addEventListener('touchstart', blockPinch, { passive: false });
    document.addEventListener('touchmove', blockPinch, { passive: false });

    document.addEventListener('wheel', (event) => {
        if (event.ctrlKey) {
            event.preventDefault();
        }
    }, { passive: false, capture: true });

    document.addEventListener('keydown', (event) => {
        if (! (event.ctrlKey || event.metaKey) || event.altKey) {
            return;
        }

        if (event.key === '+' || event.key === '-' || event.key === '=' || event.key === '_' || event.key === '0') {
            event.preventDefault();
        }
    });
}

preventPageZoom();

function syncKeyboardInset() {
    const viewport = window.visualViewport;

    if (! viewport) {
        return;
    }

    const inset = Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop);

    document.documentElement.style.setProperty('--lb-keyboard', `${Math.round(inset)}px`);
}

if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', syncKeyboardInset);
    window.visualViewport.addEventListener('scroll', syncKeyboardInset);
    syncKeyboardInset();
}

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-keep-period]') : null;

    if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    const current = new URL(window.location.href);
    const target = new URL(link.href, window.location.origin);

    for (const key of ['month', 'year']) {
        const value = current.searchParams.get(key);

        if (value) {
            target.searchParams.set(key, value);
        } else {
            target.searchParams.delete(key);
        }
    }

    link.href = `${target.pathname}${target.search}${target.hash}`;
}, true);
