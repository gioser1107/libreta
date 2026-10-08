/**
 * La K solo aparece al cambiar de sección (enlace con wire:navigate).
 * Vive en <html> para que el recambio del body no la borre a mitad de camino.
 */
const LOADER_ID = 'lb-page-loader';
const DEAD_CLICK_MS = 450;
const FORCE_HIDE_MS = 8000;

let template = null;
let showToken = 0;
let navigating = false;
let deadClickTimer = null;
let forceTimer = null;

function remember(el) {
    if (!template) {
        template = el.cloneNode(true);
        template.hidden = true;
    }
}

function ensureLoader() {
    const nodes = [...document.querySelectorAll(`#${LOADER_ID}`)];
    let el = nodes.find((node) => node.parentElement === document.documentElement) || nodes[0];

    if (!el && template) {
        el = template.cloneNode(true);
        document.documentElement.appendChild(el);
    }

    if (!el) {
        return null;
    }

    remember(el);

    if (el.parentElement !== document.documentElement) {
        document.documentElement.appendChild(el);
    }

    document.querySelectorAll(`#${LOADER_ID}`).forEach((node) => {
        if (node !== el) {
            node.remove();
        }
    });

    return el;
}

function showLoader() {
    const el = ensureLoader();

    if (!el) {
        return 0;
    }

    showToken += 1;
    const token = showToken;
    el.hidden = false;
    el.setAttribute('aria-hidden', 'false');
    el.setAttribute('aria-busy', 'true');

    window.clearTimeout(forceTimer);
    forceTimer = window.setTimeout(() => {
        if (token === showToken) {
            hideNow();
        }
    }, FORCE_HIDE_MS);

    return token;
}

function hideNow() {
    window.clearTimeout(deadClickTimer);
    window.clearTimeout(forceTimer);
    navigating = false;
    showToken += 1;

    document.querySelectorAll(`#${LOADER_ID}`).forEach((el) => {
        el.hidden = true;
        el.setAttribute('aria-hidden', 'true');
        el.removeAttribute('aria-busy');
    });
}

function isModuleLink(event) {
    if (event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    const link = event.target?.closest?.('a[wire\\:navigate]');

    if (!link || link.target === '_blank' || link.hasAttribute('download')) {
        return false;
    }

    return true;
}

function bootPageLoader() {
    if (window.__lbPageLoader) {
        return;
    }

    window.__lbPageLoader = true;

    ensureLoader();
    hideNow();

    document.addEventListener('click', (event) => {
        if (!isModuleLink(event)) {
            return;
        }

        const token = showLoader();
        window.clearTimeout(deadClickTimer);
        deadClickTimer = window.setTimeout(() => {
            if (token === showToken && !navigating) {
                hideNow();
            }
        }, DEAD_CLICK_MS);
    }, true);

    document.addEventListener('livewire:navigate', () => {
        navigating = true;
        window.clearTimeout(deadClickTimer);
        showLoader();
    });
    document.addEventListener('livewire:navigated', hideNow);
    window.addEventListener('pageshow', hideNow);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPageLoader, { once: true });
} else {
    bootPageLoader();
}
