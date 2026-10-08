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
    <h1 class="sr-only">Iniciar sesión</h1>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form wire:submit="login" class="lb-stack">
        <div>
            <x-input-label for="email" class="sr-only" value="Correo" />
            <x-text-input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" placeholder="Correo" inputmode="email" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" class="sr-only" value="Clave" />
            <x-password-input wire:model="form.password" id="password" name="password" required autocomplete="current-password" placeholder="Clave" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="lb-auth-remember">
            <input wire:model="form.remember" id="remember" type="checkbox" class="lb-check" name="remember">
            <span>Recordarme en este dispositivo</span>
        </label>

        <div class="lb-form-actions" data-passkey-login>
            <div class="lb-auth-actions">
                <button type="submit" wire:loading.attr="disabled" class="lb-btn lb-btn-primary lb-auth-enter">
                    <span wire:loading.remove wire:target="login">Entrar</span>
                    <span wire:loading wire:target="login">Entrando…</span>
                </button>

                <button type="button" data-passkey-login-button hidden class="lb-btn lb-btn-ghost lb-btn-face" aria-label="Face ID">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" d="M8 3H5a2 2 0 0 0-2 2v3"></path>
                        <path stroke-linecap="round" d="M16 3h3a2 2 0 0 1 2 2v3"></path>
                        <path stroke-linecap="round" d="M8 21H5a2 2 0 0 1-2-2v-3"></path>
                        <path stroke-linecap="round" d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
                        <path stroke-linecap="round" d="M9 10v1M15 10v1"></path>
                        <path stroke-linecap="round" d="M12 10v3.2a1.8 1.8 0 0 1-1.8 1.8"></path>
                        <path stroke-linecap="round" d="M9.2 16.2c.6.7 1.6 1.1 2.8 1.1s2.2-.4 2.8-1.1"></path>
                    </svg>
                    <span data-passkey-label class="sr-only">Face ID</span>
                </button>
            </div>

            <p data-passkey-error class="lb-error" hidden></p>

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
