<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'AmbatuWork' }}</title>

    <!-- Google Fonts: Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            background: #FDCB40;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
        }
    </style>
</head>

<body class="min-h-full text-[#6E5003] flex flex-col antialiased selection:bg-orange-500/20 pb-24">
    <main class="flex-grow">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <!-- Circular Back Button -->
            <div class="mb-8">
                <a href="{{ $backUrl ?? route('dashboard') }}" 
                   wire:navigate
                   class="inline-flex w-12 h-12 rounded-full bg-white text-[#604B10] items-center justify-center hover:bg-white/90 transition-colors select-none cursor-pointer outline-none border-none">
                    <x-heroicon-s-arrow-left class="w-6 h-6"/>
                </a>
            </div>

            <!-- Page Content -->
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
    <x-dashboard.toast />
</body>

</html>
