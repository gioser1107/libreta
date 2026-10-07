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

    if (error instanceof InvalidDomainError) {
        return 'Abre la libreta en la misma dirección donde activaste Face ID.';
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

function bindPasskeyLogin() {
    const zone = document.querySelector('[data-passkey-login]');

    if (!zone || zone.dataset.bound === '1' || !Passkeys.isSupported()) {
        return;
    }

    zone.dataset.bound = '1';
    zone.hidden = false;
    zone.querySelector('[data-passkey-login-button]')?.addEventListener('click', () => {
        loginWithPasskey(zone);
    });
}

document.addEventListener('DOMContentLoaded', bindPasskeyLogin);
document.addEventListener('livewire:navigated', bindPasskeyLogin);
document.addEventListener('livewire:init', () => {
    window.Livewire.on('passkey-register', () => {
        registerPasskey();
    });
});
