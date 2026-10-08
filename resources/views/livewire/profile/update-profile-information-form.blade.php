<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public bool $editing = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->editing = false;

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function cancelEditing(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->editing = false;
        $this->resetValidation();
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <div class="lb-who">
        <span class="lb-avatar is-lg" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
        <div class="lb-who-text">
            <strong>{{ Auth::user()->name }}</strong>
            <span>{{ Auth::user()->email }}</span>
        </div>
        @unless ($editing)
            <button type="button" wire:click="$set('editing', true)" class="lb-chip">Editar</button>
        @endunless
    </div>

    @if ($editing)
        <form wire:submit="updateProfileInformation" class="lb-who-edit">
            <label class="lb-field" for="name">
                <span class="lb-label">Nombre</span>
                <x-text-input wire:model="name" id="name" name="name" type="text" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </label>

            <label class="lb-field" for="email">
                <span class="lb-label">Correo</span>
                <x-text-input wire:model="email" id="email" name="email" type="email" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <p class="lb-help">
                        Este correo no está verificado.

                        <button wire:click.prevent="sendVerification" class="lb-link">
                            Enviar de nuevo
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="lb-help">Te enviamos un enlace nuevo.</p>
                    @endif
                @endif
            </label>

            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="cancelEditing" class="lb-btn lb-btn-ghost">Cancelar</button>
                <x-primary-button>Guardar</x-primary-button>
            </div>
        </form>
    @endif
</section>
