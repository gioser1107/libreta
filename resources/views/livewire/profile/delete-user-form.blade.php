<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="lb-account-end">
    <button
        type="button"
        class="lb-quiet"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Eliminar cuenta</button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="lb-form">
            <h2 class="lb-section-title">¿Eliminar la cuenta?</h2>

            <p class="lb-help">
                Se borran tus bancos, ingresos, egresos y el acceso. No se puede deshacer.
            </p>

            <label class="lb-field" for="password">
                <span class="lb-label">Clave</span>
                <x-password-input wire:model="password" id="password" name="password" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </label>

            <div class="lb-dialog-foot">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button>
                    Eliminar
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
