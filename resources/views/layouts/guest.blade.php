<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => request()->cookie('lb-theme') === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('layouts.partials.pwa-head')

        <title>{{ config('libreta.company') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="lb-app antialiased">
        @include('layouts.partials.pwa-body')
        <div class="lb-guest">
            <div class="lb-guest-card">
                <a href="/" wire:navigate class="lb-brand">
                    <x-application-logo />
                </a>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
