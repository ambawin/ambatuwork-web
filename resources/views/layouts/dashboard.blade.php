<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard | AmbatuWork' }}</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS v4 -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            /* background: linear-gradient(to bottom, #FDCB40 75%, #977926 100%); */
            background: #FDCB40;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
        }
    </style>
</head>

<body class="min-h-full text-[#6E5003] flex flex-col antialiased selection:bg-orange-500/20 pb-24">
    <!-- The bar -->
    <header class="fixed top-0 left-0 right-0 z-50 flex justify-center items-center gap-3 w-full mt-4">
        <div class="relative project-dropdown">
            <!-- The button -->
            <button
                class="flex gap-4 bg-white text-[#604B10] px-8 py-2 rounded-full text-lg font-bold items-center cursor-pointer select-none outline-none border-none"
                onclick="toggleDropdown(event)">
                <p>{{ $activeProject->name ?? 'No Project' }}</p>
                <svg class="w-4 h-4 text-[#604B10] transition-transform duration-200 dropdown-icon" viewBox="0 0 24 24"
                fill="currentColor">
                <path
                    d="M5.25 8.5h13.5c.67 0 1.03.79.59 1.3l-6.75 7.78c-.33.38-.95.38-1.28 0L4.66 9.8c-.44-.51-.08-1.3.59-1.3z" />
                </svg>
            </button>

            <!-- The dropdown menu -->
            <ul class="absolute top-[calc(100%+8px)] left-0 min-w-[240px] bg-white rounded-3xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] m-0 p-2 list-none z-50 hidden dropdown-menu">
                <li>
                    <a href="{{ route('projects.create') }}"
                        wire:navigate
                        class="block px-6 py-2.5 text-green-700 hover:bg-green-50 no-underline text-[16px] font-bold transition-colors duration-150 rounded-full flex items-center gap-1.5">
                        <x-heroicon-s-plus class="w-4 h-4"/>
                        <span>Add new project</span>
                    </a>
                </li>
                @if (isset($joinedProjects) && !$joinedProjects->isEmpty())
                    <hr class="my-1 border-[#6E5003]/10" />
                    @foreach($joinedProjects as $project)
                        <li>
                            <a href="{{ request()->url() }}?project_id={{ $project['id'] }}"
                                wire:navigate
                                class="block px-6 py-2.5 text-[#604B10] no-underline text-[16px] font-medium transition-colors duration-150 rounded-full {{ isset($activeProject) && $activeProject->id == $project['id'] ? 'bg-[#FDCB40]/40 font-bold' : 'hover:bg-[#FDCB40]' }}">
                                {{ $project['name'] }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>

        {{-- Notification --}}
        <a href="{{ route('notifications') }}" 
           wire:navigate
           class="w-11 h-11 rounded-full flex items-center justify-center transition-all duration-150 {{ request()->routeIs('notifications') ? 'bg-[#604B10] text-[#FDCB40]' : 'bg-white text-[#604B10] hover:bg-white/80' }} shadow-md"
           title="Notifications">
            <x-heroicon-s-bell class="w-6 h-6"/>
        </a>
    </header>

    <!-- Main Content Slot -->
    <main class="flex-grow mt-16">
        {{ $slot }}
    </main> 

    <!-- Bottom Navigation -->
    <footer class="fixed bottom-0 left-0 right-0 z-50 flex justify-center w-full mb-4 pointer-events-none">
        <div class="flex items-center justify-center gap-4 w-full pointer-events-auto">

            {{-- User / Profile link --}}
            <div class="bg-white p-2 rounded-full flex items-center shadow-md">
                <a href="{{ route('profile') }}" 
                   wire:navigate
                   class="text-[#604B10] w-11 h-11 rounded-full flex items-center justify-center transition-colors duration-150 {{ request()->routeIs('profile') ? 'bg-[#FDCB40]' : 'hover:bg-[#FDCB40]/40' }} cursor-pointer outline-none overflow-hidden p-0"
                   title="Profile">
                    @if(auth()->check() && auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover rounded-full">
                    @elseif(auth()->check())
                        <div class="w-full h-full bg-[#FDCB40]/30 text-[#604B10] font-black flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @else
                        <x-heroicon-s-user class="w-6 h-6"/>
                    @endif
                </a>
            </div>
            
            {{-- Tabs --}}
            <div class="bg-white p-2 rounded-full flex items-center gap-1 shadow-[0_4px_20px_rgba(0,0,0,0.08)]">
                <a href="{{ route('dashboard') }}" 
                   wire:navigate
                   class="text-[#604B10] px-8 py-2.5 rounded-full flex items-center justify-center transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-[#FDCB40]' : 'hover:bg-[#FDCB40]/40' }}"
                   title="Dashboard">
                    <x-heroicon-s-home class="w-6 h-6"/>
                </a>
                
                <a href="{{ route('backlog') }}" 
                   wire:navigate
                   class="text-[#604B10] px-8 py-2.5 rounded-full flex items-center justify-center transition-all duration-150 {{ request()->routeIs('backlog') ? 'bg-[#FDCB40]' : 'hover:bg-[#FDCB40]/40' }}"
                   title="Backlog">
                    <x-heroicon-s-numbered-list class="w-6 h-6"/>
                </a>

                <a href="{{ route('sprint-board') }}" 
                   wire:navigate
                   class="text-[#604B10] px-8 py-2.5 rounded-full flex items-center justify-center transition-all duration-150 {{ request()->routeIs('sprint-board') ? 'bg-[#FDCB40]' : 'hover:bg-[#FDCB40]/40' }}"
                   title="Sprint Board">
                    <x-heroicon-s-rectangle-stack class="w-6 h-6"/>
                </a>

                <a href="{{ route('settings') }}" 
                   wire:navigate
                   class="text-[#604B10] px-8 py-2.5 rounded-full flex items-center justify-center transition-all duration-150 {{ request()->routeIs('settings') ? 'bg-[#FDCB40]' : 'hover:bg-[#FDCB40]/40' }}"
                   title="Settings">
                    <x-heroicon-s-cog-6-tooth class="w-6 h-6"/>
                </a>
            </div>
        </div>
    </footer>

    @livewireScripts
    <script>
        function toggleDropdown(event) {
            event.stopPropagation();
            const container = event.currentTarget.closest('.project-dropdown');
            const menu = container.querySelector('.dropdown-menu');
            const icon = container.querySelector('.dropdown-icon');

            menu.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        window.addEventListener('click', function(event) {
            document.querySelectorAll('.project-dropdown').forEach(dropdown => {
                if (!dropdown.contains(event.target)) {
                    dropdown.querySelector('.dropdown-menu').classList.add('hidden');
                    dropdown.querySelector('.dropdown-icon').classList.remove('rotate-180');
                }
            });
        });
    </script>
    <x-dashboard.toast />
</body>

</html>
