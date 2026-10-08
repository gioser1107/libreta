@php
    $lbDark = request()->cookie('lb-theme') === 'dark';
    $lbSplashes = [
        ['file' => 'iphone-se', 'w' => 375, 'h' => 667, 'r' => 2],
        ['file' => 'iphone-x', 'w' => 375, 'h' => 812, 'r' => 3],
        ['file' => 'iphone-xr', 'w' => 414, 'h' => 896, 'r' => 2],
        ['file' => 'iphone-12', 'w' => 390, 'h' => 844, 'r' => 3],
        ['file' => 'iphone-14-pro', 'w' => 393, 'h' => 852, 'r' => 3],
        ['file' => 'iphone-14-plus', 'w' => 428, 'h' => 926, 'r' => 3],
        ['file' => 'iphone-14-pm', 'w' => 430, 'h' => 932, 'r' => 3],
        ['file' => 'iphone-16-pro', 'w' => 402, 'h' => 874, 'r' => 3],
        ['file' => 'iphone-16-pm', 'w' => 440, 'h' => 956, 'r' => 3],
    ];
@endphp

<meta name="theme-color" content="{{ $lbDark ? '#0a0a0a' : '#ffffff' }}">
<meta name="color-scheme" content="light dark">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ $lbDark ? 'black' : 'default' }}">
<meta name="apple-mobile-web-app-title" content="{{ config('libreta.company') }}">
<meta name="application-name" content="{{ config('libreta.company') }}">
<meta name="format-detection" content="telephone=no">

<link rel="manifest" href="{{ route('pwa.manifest', absolute: false) }}">
<link rel="icon" href="{{ asset('icons/favicon-32.png') }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" sizes="180x180">

@foreach ($lbSplashes as $splash)
    <link rel="apple-touch-startup-image" media="screen and (device-width: {{ $splash['w'] }}px) and (device-height: {{ $splash['h'] }}px) and (-webkit-device-pixel-ratio: {{ $splash['r'] }}) and (orientation: portrait) and (prefers-color-scheme: light)" href="{{ asset('splash/'.$splash['file'].'-light.png') }}">
    <link rel="apple-touch-startup-image" media="screen and (device-width: {{ $splash['w'] }}px) and (device-height: {{ $splash['h'] }}px) and (-webkit-device-pixel-ratio: {{ $splash['r'] }}) and (orientation: portrait) and (prefers-color-scheme: dark)" href="{{ asset('splash/'.$splash['file'].'-dark.png') }}">
@endforeach

@include('layouts.partials.theme-boot')

<style>
    #lb-splash {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
    }
    html.dark #lb-splash { background: #0a0a0a; }
    html.lb-booted #lb-splash { display: none !important; }
    #lb-splash.is-done {
        opacity: 0;
        transition: opacity .35s ease;
        pointer-events: none;
    }
    #lb-splash img {
        width: 96px;
        height: 96px;
        border-radius: 22px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .14);
    }
</style>
