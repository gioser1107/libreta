import {
    InvalidDomainError,
    NotSupportedError,
    PasskeyExistsError,
    Passkeys,
    UserCancelledError,
} from '@laravel/passkeys';

function spanishMessage(error, fallback) {
    if (error instanceof UserCancelledError) {
        return '';
    }

    if (error instanceof PasskeyExistsError) {
        return 'Este dispositivo ya tiene Face ID activado.';
    }

    if (error instanceof NotSupportedError) {
        return 'Este navegador no puede usar Face ID.';
    }

    if (error instanceof InvalidDomainError || (error instanceof Error && /invalid domain|invalid RP ID/i.test(error.message))) {
        return 'Face ID no funciona en una dirección IP. Ábrela en localhost, con el mismo puerto.';
    }

    if (error instanceof Error && error.message === 'Server Error') {
        return 'El servidor no pudo preparar Face ID. Recarga la página e inténtalo otra vez.';
    }

    if (error instanceof Error && error.message.includes('Password confirmation')) {
        return 'Confirma tu contraseña e inténtalo de nuevo.';
    }

    return fallback;
}

function showError(zone, message) {
    const error = zone.querySelector('[data-passkey-error]');

    if (!error) {
        return;
    }

    error.hidden = message === '';
    error.textContent = message;
}

function setBusy(button, busy, label) {
    if (!button) {
        return;
    }

    button.disabled = busy;
    const text = button.querySelector('[data-passkey-label]');

    if (text && label) {
        text.textContent = label;
    }
}

async function loginWithPasskey(zone) {
    const button = zone.querySelector('[data-passkey-login-button]');

    setBusy(button, true, 'Esperando');
    showError(zone, '');

    try {
        const response = await Passkeys.verify({
            remember: () => document.querySelector('#remember')?.checked ?? false,
        });

        window.location.assign(response.redirect || '/dashboard');
    } catch (error) {
        showError(zone, spanishMessage(
            error,
            'No se pudo entrar con Face ID. Si aún no lo activaste, entra con tu contraseña y actívalo en Cuenta.',
        ));
        setBusy(button, false, 'Face ID');
    }
}

async function registerPasskey() {
    const zone = document.querySelector('[data-passkey-register]');

    if (!zone) {
        return;
    }

    const button = zone.querySelector('[data-passkey-register-button]');

    setBusy(button, true, 'Esperando');
    showError(zone, '');

    try {
        await Passkeys.register({ name: 'Este dispositivo' });
        window.location.reload();
    } catch (error) {
        showError(zone, spanishMessage(error, 'No se pudo activar Face ID en este dispositivo.'));
        setBusy(button, false, 'Activar Face ID');
    }
}

const desktopPointer = window.matchMedia('(hover: hover) and (pointer: fine)');

function passkeyLoginIsAvailable() {
    return Passkeys.isSupported() && !desktopPointer.matches;
}

function bindPasskeyLogin() {
    const zone = document.querySelector('[data-passkey-login]');

    if (!zone) {
        return;
    }

    const button = zone.querySelector('[data-passkey-login-button]');
    const available = passkeyLoginIsAvailable();

    if (button) {
        button.hidden = !available;
    }

    if (!available || zone.dataset.bound === '1') {
        return;
    }

    zone.dataset.bound = '1';
    button?.addEventListener('click', () => {
        loginWithPasskey(zone);
    });
}

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element
        ? event.target.closest('[data-passkey-register-button]')
        : null;

    if (!button) {
        return;
    }

    const zone = button.closest('[data-passkey-register]');

    if (!zone?.hasAttribute('data-passkey-ready')) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    registerPasskey();
}, true);

document.addEventListener('DOMContentLoaded', bindPasskeyLogin);
document.addEventListener('livewire:navigated', bindPasskeyLogin);
desktopPointer.addEventListener('change', bindPasskeyLogin);
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => bindPasskeyLogin());
    });
});
