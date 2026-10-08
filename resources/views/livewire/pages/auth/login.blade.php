<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="lb-guest-title">Iniciar sesión</h1>
    <p class="lb-help">Entra con Face ID o con el correo de tu cuenta.</p>

    <div data-passkey-login hidden class="lb-stack">
        <button type="button" data-passkey-login-button class="lb-btn lb-btn-primary lb-btn-block">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" d="M8 3H5a2 2 0 0 0-2 2v3"></path>
                <path stroke-linecap="round" d="M16 3h3a2 2 0 0 1 2 2v3"></path>
                <path stroke-linecap="round" d="M8 21H5a2 2 0 0 1-2-2v-3"></path>
                <path stroke-linecap="round" d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
                <path stroke-linecap="round" d="M9 10v1M15 10v1"></path>
                <path stroke-linecap="round" d="M12 10v3.2a1.8 1.8 0 0 1-1.8 1.8"></path>
                <path stroke-linecap="round" d="M9.2 16.2c.6.7 1.6 1.1 2.8 1.1s2.2-.4 2.8-1.1"></path>
            </svg>
            <span data-passkey-label>Face ID</span>
        </button>
        <p data-passkey-error class="lb-error" hidden></p>
        <p class="lb-help lb-passkey-or">o con correo</p>
    </div>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form wire:submit="login" class="lb-stack">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-1" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input wire:model="form.password" id="password" class="mt-1" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="inline-flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox" class="lb-check" name="remember">
            <span class="lb-help">{{ __('Remember me') }}</span>
        </label>

        <div class="lb-form-actions">
            <button type="submit" wire:loading.attr="disabled" class="lb-btn lb-btn-primary lb-btn-block">
                <span wire:loading.remove wire:target="login">Entrar</span>
                <span wire:loading wire:target="login">Entrando…</span>
            </button>

            @if (Route::has('password.request'))
                <a class="lb-link" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu clave?
                </a>
            @endif

            @if (Route::has('register'))
                <a class="lb-link" href="{{ route('register') }}" wire:navigate>
                    Crear cuenta
                </a>
            @endif
        </div>
    </form>
</div>
