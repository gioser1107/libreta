<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => request()->cookie('lb-theme') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.pwa-head')

    <title>{{ config('libreta.company') }}</title>
    @include('layouts.partials.social-meta')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="lb-app lb-has-tabbar antialiased" x-data="{ minimized: false }">
    @include('layouts.partials.pwa-body')
    <div class="lb-shell">
        <aside class="lb-side" :class="minimized && 'is-narrow'">
            <div class="lb-side-top">
                <a href="{{ route('dashboard') }}" wire:navigate class="lb-brand">
                    <x-application-logo />
                </a>
                <button type="button" @click="minimized = !minimized" class="lb-icon-btn" :title="minimized ? 'Expandir menú' : 'Colapsar menú'">
                    <svg x-show="!minimized" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                    <svg x-show="minimized" x-cloak width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <nav class="lb-nav" aria-label="Secciones">
                @include('layouts.partials.nav-links')
            </nav>

            <div class="lb-side-foot" x-data="{ userMenuOpen: false }">
                <button type="button" @click="userMenuOpen = !userMenuOpen" @click.outside="userMenuOpen = false" class="lb-user">
                    <span class="lb-avatar">{{ mb_substr(Auth::user()->name ?? 'U', 0, 1) }}</span>
                    <span class="lb-user-name">{{ Auth::user()->name ?? 'Usuario' }}</span>
                </button>

                <div x-show="userMenuOpen" x-cloak class="lb-dropdown">
                    @include('layouts.partials.theme-toggle')

                    <a href="{{ route('profile') }}" wire:navigate class="lb-menu-item">Cuenta</a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="lb-menu-item">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="lb-main">
            {{ $slot }}
        </main>
    </div>

    @include('layouts.partials.tab-bar')
</body>
</html>
