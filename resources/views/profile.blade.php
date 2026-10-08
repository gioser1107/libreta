<x-app-layout>
    <div class="lb-page lb-page-form">
        <header class="lb-head">
            <div>
                @if($section)
                    <a href="{{ route('profile') }}" wire:navigate class="lb-back">Cuenta</a>
                @endif
                <h1>{{ $sectionTitle }}</h1>
            </div>
        </header>

        @if($section === null)
            <section class="lb-panel lb-settings" aria-label="Secciones de la cuenta">
                <a href="{{ route('profile', ['seccion' => 'perfil']) }}" wire:navigate class="lb-row">
                    <span>Perfil</span>
                    <span class="lb-row-value">Nombre y correo</span>
                </a>
                <a href="{{ route('profile', ['seccion' => 'fijos']) }}" wire:navigate class="lb-row">
                    <span>Cada mes</span>
                    <span class="lb-row-value">Sueldo, alquiler</span>
                </a>
                <a href="{{ route('profile', ['seccion' => 'ajustes']) }}" wire:navigate class="lb-row">
                    <span>Ajustes</span>
                    <span class="lb-row-value">Clave y apariencia</span>
                </a>
            </section>
        @elseif($section === 'perfil')
            <section class="lb-panel">
                <livewire:profile.update-profile-information-form />
            </section>
        @elseif($section === 'fijos')
            <livewire:recurring-entries />
        @else
            <section class="lb-panel lb-settings">
                @include('layouts.partials.theme-toggle', ['variant' => 'row'])

                <livewire:profile.manage-passkeys />
                <livewire:profile.update-password-form />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="lb-row">Cerrar sesión</button>
                </form>
            </section>

            <livewire:profile.delete-user-form />
        @endif
    </div>
</x-app-layout>
