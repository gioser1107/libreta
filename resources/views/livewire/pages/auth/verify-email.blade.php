<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <h1 class="lb-guest-title">Verifica tu correo</h1>
    <p class="lb-help">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <p class="lb-help lb-success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </p>
    @endif

    <div class="lb-form-actions">
        <x-primary-button wire:click="sendVerification" class="lb-btn-block">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <button wire:click="logout" type="button" class="lb-link">
            {{ __('Log Out') }}
        </button>
    </div>
</div>
