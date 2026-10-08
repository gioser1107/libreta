<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.social-meta')
    <title>{{ config('libreta.company') }}</title>
</head>
<body>
    <main>
        <h1>{{ config('libreta.company') }}</h1>
        <p>{{ config('libreta.description') }}</p>
        <img src="{{ asset('og.png') }}" alt="{{ config('libreta.company') }}" width="1200" height="630">
    </main>
</body>
</html>
