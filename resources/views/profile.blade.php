<x-app-layout>
    <div class="lb-page lb-page-form">
        <header class="lb-head">
            <div>
                <h1>Cuenta</h1>
                <p class="lb-help">{{ Auth::user()->name }} · {{ Auth::user()->email }}</p>
            </div>
        </header>

        <div class="lb-panels">
            <section class="lb-panel lg:hidden">
                @include('layouts.partials.theme-toggle')
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="lb-menu-item">Cerrar sesión</button>
                </form>
            </section>

            <section class="lb-panel lb-panel-pad">
                <livewire:profile.update-profile-information-form />
            </section>

            <section class="lb-panel lb-panel-pad">
                <livewire:profile.manage-passkeys />
            </section>

            <section class="lb-panel lb-panel-pad">
                <livewire:profile.update-password-form />
            </section>

            <section class="lb-panel lb-panel-pad">
                <livewire:profile.delete-user-form />
            </section>
        </div>
    </div>
</x-app-layout>
