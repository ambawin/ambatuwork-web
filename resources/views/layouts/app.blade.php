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
    <nav x-data="{ mobileMenuOpen: false }" class="fixed top-0 left-0 right-0 z-50 bg-[#FDCB40]">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('landing') }}" class="text-2xl font-black tracking-tight text-[#604B10] hover:scale-102 transition-transform duration-200">
                AmbatuWork
            </a>
            
            <!-- Desktop Navigation Links -->
            <div class="hidden md:flex space-x-6 text-sm font-bold items-center">
                <a href="{{ route('pricing') }}" class="text-[#977926] hover:text-[#604B10] transition">Pricing</a>
                <a href="https://github.com/ambawin/ambatuwork-android" class="text-[#977926] hover:text-[#604B10] transition">Download</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-block bg-[#604B10] text-white px-6 py-2.5 rounded-full hover:bg-white/70 transition">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="inline-block bg-white text-[#604B10] px-6 py-2.5 rounded-full hover:bg-white/70 transition">Log in</a>
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
                        class="block w-full text-center bg-[#604B10] text-white px-6 py-3 rounded-full hover:bg-[#977926] transition text-sm"
                    >
                        Dashboard
                    </a>
                @else
                    <a 
                        href="{{ route('login') }}" 
                        @click="mobileMenuOpen = false"
                        class="block w-full text-center bg-white text-[#604B10] px-6 py-3 rounded-full hover:bg-[#FDCB40] transition border border-[#6E5003]/10 text-sm"
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

    <!-- Footer -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="#604b10" fill-opacity="1" d="M0,64L10.9,96C21.8,128,44,192,65,218.7C87.3,245,109,235,131,234.7C152.7,235,175,245,196,256C218.2,267,240,277,262,261.3C283.6,245,305,203,327,197.3C349.1,192,371,224,393,202.7C414.5,181,436,107,458,90.7C480,75,502,117,524,149.3C545.5,181,567,203,589,192C610.9,181,633,139,655,144C676.4,149,698,203,720,197.3C741.8,192,764,128,785,90.7C807.3,53,829,43,851,42.7C872.7,43,895,53,916,80C938.2,107,960,149,982,149.3C1003.6,149,1025,107,1047,112C1069.1,117,1091,171,1113,202.7C1134.5,235,1156,245,1178,224C1200,203,1222,149,1244,160C1265.5,171,1287,245,1309,272C1330.9,299,1353,277,1375,240C1396.4,203,1418,149,1429,122.7L1440,96L1440,320L1429.1,320C1418.2,320,1396,320,1375,320C1352.7,320,1331,320,1309,320C1287.3,320,1265,320,1244,320C1221.8,320,1200,320,1178,320C1156.4,320,1135,320,1113,320C1090.9,320,1069,320,1047,320C1025.5,320,1004,320,982,320C960,320,938,320,916,320C894.5,320,873,320,851,320C829.1,320,807,320,785,320C763.6,320,742,320,720,320C698.2,320,676,320,655,320C632.7,320,611,320,589,320C567.3,320,545,320,524,320C501.8,320,480,320,458,320C436.4,320,415,320,393,320C370.9,320,349,320,327,320C305.5,320,284,320,262,320C240,320,218,320,196,320C174.5,320,153,320,131,320C109.1,320,87,320,65,320C43.6,320,22,320,11,320L0,320Z"></path></svg>
    <footer class="bg-[#604b10]">
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 text-[#FDCB40]/40 mb-24">
            <div class="max-w-6xl mx-auto flex flex-col gap-4">
                <p class="font-bold text-[#FDCB40]/70">Ambawin Official</p>
                <a href="https://ambatu.win" target="_blank">ambatu.win</a>
                <a href="#">Our Developers</a>
            </div>
            <div class="max-w-6xl mx-auto flex flex-col gap-4">
                <p class="font-bold text-[#FDCB40]/70">AmbatuWork</p>
                <a href="{{ route('landing') }}">Home</a>
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="#">SCRUM Guide</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
            </div>
            <div class="max-w-6xl mx-auto flex flex-col gap-4">
                <p class="font-bold text-[#FDCB40]/70">Contact</p>
                <a href="mailto: ambawinofficial@gmail.com">ambawinofficial@gmail.com</a>
                <a href="https://instagram.com/ambatuwork" target="_blank">Instagram @ambatuwork</a>
            </div>
        </div>
        <img src="{{ asset('images/ambatuwork-footer.png') }}" alt="ambatuwork footer" class="w-full h-auto" />
    </footer>

    @livewireScripts
</body>

</html>
