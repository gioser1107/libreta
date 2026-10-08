<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public bool $confirming = false;

    public ?string $pendingAction = null;

    public ?int $pendingPasskeyId = null;

    #[Computed]
    public function passkeys()
    {
        return Auth::user()->passkeys()->latest()->get();
    }

    public function startRegistration(): void
    {
        $this->pendingAction = 'register';
        $this->pendingPasskeyId = null;
        $this->continuePending();
    }

    public function deletePasskey(int $passkeyId): void
    {
        $this->pendingAction = 'delete';
        $this->pendingPasskeyId = $passkeyId;
        $this->continuePending();
    }

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.current_password' => 'La clave no coincide.',
        ]);

        session(['auth.password_confirmed_at' => time()]);

        $this->reset('password');
        $this->confirming = false;
        $this->continuePending();
    }

    protected function continuePending(): void
    {
        if (! $this->passwordIsConfirmed()) {
            $this->confirming = true;

            return;
        }

        if ($this->pendingAction === 'register') {
            $this->pendingAction = null;
            $this->dispatch('passkey-register');

            return;
        }

        if ($this->pendingAction !== 'delete') {
            return;
        }

        $passkeyId = $this->pendingPasskeyId;
        $this->pendingAction = null;
        $this->pendingPasskeyId = null;

        $passkey = Auth::user()->passkeys()->whereKey($passkeyId)->first();

        abort_if($passkey === null, 404);

        app(DeletePasskey::class)(Auth::user(), $passkey);

        unset($this->passkeys);
    }

    protected function passwordIsConfirmed(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        return (time() - $confirmedAt) < (int) config('auth.password_timeout', 10800);
    }
}; ?>

<section>
    <header>
        <h2 class="lb-section-title">Face ID</h2>
        <p class="lb-help">
            Actívalo en este teléfono. En el acceso, el botón Face ID abre la cara o la huella.
        </p>
    </header>

    @if ($this->passkeys->isNotEmpty())
        <ul class="lb-passkey-list">
            @foreach ($this->passkeys as $passkey)
                <li class="lb-passkey-row" wire:key="passkey-{{ $passkey->id }}">
                    <div>
                        <p>{{ $passkey->name }}</p>
                        <p class="lb-help">
                            {{ $passkey->authenticator ?: 'Este dispositivo' }}
                            · {{ $passkey->created_at?->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="deletePasskey({{ $passkey->id }})"
                        wire:confirm="¿Quitar Face ID de este dispositivo?"
                        class="lb-passkey-remove"
                    >
                        Quitar
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="lb-stack" data-passkey-register>
        <p data-passkey-error class="lb-error" hidden></p>

        @if ($confirming)
            <form wire:submit="confirmPassword" class="lb-stack">
                <div>
                    <x-input-label for="passkey_password" value="Clave" />
                    <x-password-input wire:model="password" id="passkey_password" class="mt-1" autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    <p class="lb-help">Hace falta tu clave una vez. Después el teléfono pide la cara o la huella.</p>
                </div>
                <x-primary-button>Confirmar y continuar</x-primary-button>
            </form>
        @else
            <button type="button" wire:click="startRegistration" data-passkey-register-button class="lb-btn lb-btn-primary">
                <span data-passkey-label>Activar Face ID</span>
            </button>
        @endif
    </div>
</section>
