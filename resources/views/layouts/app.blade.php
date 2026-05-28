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
    <nav x-data="{ mobileMenuOpen: false }" class="fixed top-0 left-0 right-0 z-50 bg-[#FDCB40] shadow-[0_2px_15px_rgba(0,0,0,0.02)]">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('landing') }}" class="text-2xl font-black tracking-tight text-[#604B10] hover:scale-102 transition-transform duration-200">
                AmbatuWork
            </a>
            
            <!-- Desktop Navigation Links -->
            <div class="hidden md:flex space-x-6 text-sm font-bold items-center">
                <a href="{{ route('pricing') }}" class="text-[#977926] hover:text-[#604B10] transition">Pricing</a>
                <a href="https://github.com/ambawin/ambatuwork-android" class="text-[#977926] hover:text-[#604B10] transition">Download</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-block bg-[#604B10] text-white px-6 py-2.5 rounded-full hover:bg-white/70 transition shadow-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="inline-block bg-white text-[#604B10] px-6 py-2.5 rounded-full hover:bg-white/70 transition shadow-sm">Log in</a>
                @endauth
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center md:hidden">
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    type="button" 
                    class="text-[#604B10] hover:text-[#977926] focus:outline-none transition p-1 cursor-pointer"
                    aria-label="Toggle menu"
                >
                    <!-- Hamburger icon when menu is closed -->
                    <svg x-show="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <!-- Close icon when menu is open -->
                    <svg x-show="mobileMenuOpen" style="display: none;" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Collapsed Navigation Dropdown -->
        <div 
            x-show="mobileMenuOpen" 
            style="display: none;"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="md:hidden bg-[#FDCB40] border-t border-[#6E5003]/10 pb-6 px-6 flex flex-col space-y-4 font-bold shadow-lg"
        >
            <a 
                href="{{ route('pricing') }}" 
                @click="mobileMenuOpen = false"
                class="text-[#977926] pl-1 hover:text-[#604B10] transition py-2 text-base"
            >
                Pricing
            </a>
            <a 
                href="https://github.com/ambawin/ambatuwork-android" 
                @click="mobileMenuOpen = false"
                class="text-[#977926] hover:text-[#604B10] transition py-2 pl-1 text-base"
            >
                Download
            </a>
            <div class="pt-2 border-t border-[#6E5003]/5">
                @auth
                    <a 
                        href="{{ route('dashboard') }}" 
                        @click="mobileMenuOpen = false"
                        class="block w-full text-center bg-[#604B10] text-white px-6 py-3 rounded-full hover:bg-[#977926] transition shadow-sm text-sm"
                    >
                        Dashboard
                    </a>
                @else
                    <a 
                        href="{{ route('login') }}" 
                        @click="mobileMenuOpen = false"
                        class="block w-full text-center bg-white text-[#604B10] px-6 py-3 rounded-full hover:bg-[#FDCB40] transition shadow-sm border border-[#6E5003]/10 text-sm"
                    >
                        Log in
                    </a>
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
