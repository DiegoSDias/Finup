<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FINUP') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="finup-auth antialiased" style="--finup-background: url('{{ asset('images/finup/background.png') }}')">
    <a href="{{ route('dashboard') }}" class="finup-auth-back" aria-label="Voltar ao início"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 5-7 7 7 7M5 12h14"/></svg></a>
    <div class="finup-auth-shell">
        <div class="finup-auth-art" aria-hidden="true"><img src="{{ asset('images/finup/discos.png') }}" alt="" fetchpriority="high"></div>
        <main class="finup-auth-main">
            <div class="finup-auth-brand" aria-label="FINUP"><x-finup-logo /></div>
            <div class="finup-auth-content">{{ $slot }}</div>
        </main>
    </div>
</body>
</html>
