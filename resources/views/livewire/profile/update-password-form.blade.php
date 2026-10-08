<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $changing = false;

    public bool $saved = false;

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->changing = false;
        $this->saved = true;

        $this->dispatch('password-updated');
    }

    public function beginChanging(): void
    {
        $this->saved = false;
        $this->changing = true;
    }

    public function cancelChanging(): void
    {
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->resetValidation();
        $this->changing = false;
    }
}; ?>

<section>
    @if ($changing)
        <form wire:submit="updatePassword" class="lb-row-body">
            <label class="lb-field" for="update_password_current_password">
                <span class="lb-label">Clave actual</span>
                <x-password-input wire:model="current_password" id="update_password_current_password" name="current_password" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('current_password')" />
            </label>

            <label class="lb-field" for="update_password_password">
                <span class="lb-label">Nueva clave</span>
                <x-password-input wire:model="password" id="update_password_password" name="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
            </label>

            <label class="lb-field" for="update_password_password_confirmation">
                <span class="lb-label">Repetir clave</span>
                <x-password-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </label>

            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="cancelChanging" class="lb-btn lb-btn-ghost">Cancelar</button>
                <x-primary-button>Guardar</x-primary-button>
            </div>
        </form>
    @else
        <button type="button" wire:click="beginChanging" class="lb-row">
            <span>Clave</span>
            <span class="lb-row-value">{{ $saved ? 'Listo' : 'Cambiar' }}</span>
        </button>
    @endif
</section>
