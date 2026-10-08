<x-app-layout>
    <div class="lb-page lb-page-form">
        <header class="lb-head">
            <h1>Cuenta</h1>
        </header>

        <div class="lb-panels">
            <section class="lb-panel">
                <livewire:profile.update-profile-information-form />
            </section>

            <livewire:bank-manager />

            <section class="lb-panel lb-settings">
                <button
                    type="button"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @lb-theme.window="dark = !!$event.detail"
                    @click="window.lbSetTheme(!dark, $event)"
                    class="lb-row"
                >
                    <span>Tema</span>
                    <span class="lb-row-value" x-text="dark ? 'Oscuro' : 'Claro'">{{ request()->cookie('lb-theme') === 'dark' ? 'Oscuro' : 'Claro' }}</span>
                </button>

                <livewire:profile.manage-passkeys />
                <livewire:profile.update-password-form />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="lb-row">Cerrar sesión</button>
                </form>
            </section>
        </div>

        <livewire:profile.delete-user-form />
    </div>
</x-app-layout>
