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
            $this->confirming = false;

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

    public function passwordIsConfirmed(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        return (time() - $confirmedAt) < (int) config('auth.password_timeout', 10800);
    }
}; ?>

<section data-passkey-register @if ($this->passwordIsConfirmed()) data-passkey-ready @endif>
    <p data-passkey-error class="lb-error" hidden></p>

    @if ($confirming)
        <form wire:submit="confirmPassword" class="lb-row-body">
            <label class="lb-field" for="passkey_password">
                <span class="lb-label">Clave</span>
                <x-password-input wire:model="password" id="passkey_password" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </label>
            <p class="lb-help">Una vez. Luego pulsa Activar Face ID.</p>
            <x-primary-button>Continuar</x-primary-button>
        </form>
    @else
        <button type="button" wire:click="startRegistration" data-passkey-register-button class="lb-row">
            <span>Face ID</span>
            <span class="lb-row-value" data-passkey-label>Activar Face ID</span>
        </button>
    @endif

    @if ($this->passkeys->isNotEmpty())
        <ul class="lb-passkey-list">
            @foreach ($this->passkeys as $passkey)
                <li class="lb-passkey-row" wire:key="passkey-{{ $passkey->id }}">
                    <div>
                        <p>{{ $passkey->name }}</p>
                        <p class="lb-help">{{ $passkey->created_at?->translatedFormat('d M Y') }}</p>
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
</section>
