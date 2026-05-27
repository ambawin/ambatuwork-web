<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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
            background:  #FDCB40;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
        }
    </style>
</head>

<body class="min-h-screen text-[#6E5003] flex flex-col antialiased selection:bg-orange-500/20">
    <!-- Sticky/Fixed Top Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-[#FDCB40] shadow-[0_2px_15px_rgba(0,0,0,0.02)]">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('landing') }}" class="text-2xl font-black tracking-tight text-[#604B10] hover:scale-102 transition-transform duration-200">
                AmbatuWork
            </a>
            <div class="space-x-6 text-sm font-bold flex items-center">
                <a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'text-[#604B10] border-b-2 border-[#604B10]/30 pb-0.5' : 'text-[#977926]' }} hover:text-[#604B10] transition">Pricing</a>
                <a href="{{ route('landing') }}#download" class="text-[#977926] hover:text-[#604B10] transition">Download</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-block bg-[#604B10] text-white px-6 py-2.5 rounded-full hover:bg-[#977926] transition shadow-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="inline-block bg-white text-[#604B10] px-6 py-2.5 rounded-full hover:bg-[#FDCB40] transition shadow-sm">Log in</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content Slot -->
    <main class="flex-grow pt-20">
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>
