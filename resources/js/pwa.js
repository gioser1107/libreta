const DISMISS_KEY = 'lb-install-dismissed';
const DISMISS_FOR = 1000 * 60 * 60 * 24 * 14;

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function hideSplash() {
    const splash = document.getElementById('lb-splash');

    if (!splash) {
        return;
    }

    if (document.documentElement.classList.contains('lb-booted')) {
        splash.remove();
        return;
    }

    const started = performance.now();
    const finish = () => {
        splash.classList.add('is-done');
        window.setTimeout(() => splash.remove(), 380);
    };
    const wait = Math.max(0, 420 - (performance.now() - started));

    if (document.readyState === 'complete') {
        window.setTimeout(finish, wait);
    } else {
        window.addEventListener('load', () => window.setTimeout(finish, wait), { once: true });
    }
}

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
}

function installDismissed() {
    try {
        const raw = localStorage.getItem(DISMISS_KEY);

        if (!raw) {
            return false;
        }

        return Date.now() - Number(raw) < DISMISS_FOR;
    } catch (error) {
        return false;
    }
}

function setupInstall() {
    const root = document.getElementById('lb-install');

    if (!root || isStandalone() || installDismissed()) {
        return;
    }

    const action = document.getElementById('lb-install-action');
    const dismiss = document.getElementById('lb-install-dismiss');
    const text = document.getElementById('lb-install-text');
    const steps = document.getElementById('lb-install-steps');
    let deferred = null;
    let mode = null;

    const ua = navigator.userAgent;
    const ios = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const iosBrowser = /crios|fxios|edgios/i.test(ua);

    const show = (nextMode) => {
        mode = nextMode;
        root.hidden = false;

        if (steps) {
            steps.hidden = true;
        }

        if (text) {
            const copy = {
                ios: 'Queda en tu inicio y se abre a pantalla completa.',
                'ios-other': 'Ábrela en Safari y agrégala al inicio.',
                native: 'Se abre a pantalla completa, sin la barra del navegador.',
            };
            text.textContent = copy[nextMode] || copy.native;
        }

        if (action) {
            action.hidden = nextMode === 'ios-other';
            action.textContent = nextMode === 'ios' ? 'Cómo' : 'Instalar';
        }
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferred = event;
        show('native');
    });

    window.addEventListener('appinstalled', () => {
        root.hidden = true;
        deferred = null;
    });

    if (ios && !iosBrowser) {
        window.setTimeout(() => show('ios'), 700);
    } else if (ios && iosBrowser) {
        window.setTimeout(() => show('ios-other'), 700);
    }

    action?.addEventListener('click', async () => {
        if (mode === 'ios') {
            if (steps) {
                steps.hidden = !steps.hidden;
            }
            return;
        }

        if (!deferred) {
            return;
        }

        deferred.prompt();
        const choice = await deferred.userChoice;
        deferred = null;

        if (choice.outcome === 'accepted') {
            root.hidden = true;
        }
    });

    dismiss?.addEventListener('click', () => {
        root.hidden = true;

        try {
            localStorage.setItem(DISMISS_KEY, String(Date.now()));
        } catch (error) {}
    });
}

function setupOffline() {
    const bar = document.getElementById('lb-offline');

    if (!bar) return;

    const sync = () => {
        bar.hidden = navigator.onLine;
    };

    window.addEventListener('online', sync);
    window.addEventListener('offline', sync);
    sync();
}

if (isStandalone()) {
    document.documentElement.classList.add('lb-standalone');
}

hideSplash();
registerServiceWorker();
setupInstall();
setupOffline();
