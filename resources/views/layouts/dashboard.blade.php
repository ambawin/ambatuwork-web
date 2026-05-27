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
            background: linear-gradient(to bottom, #FDCB40 75%, #977926 100%);
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
        }
    </style>
</head>

<body class="min-h-full text-[#6E5003] flex flex-col antialiased selection:bg-orange-500/20">
    <!-- The bar -->
    <header class="flex justify-center w-full mt-4">
        <div class="relative project-dropdown">
            <!-- The button -->
            <button
                class="flex gap-4 bg-white text-[#604B10] px-8 py-2 rounded-full text-lg font-bold items-center cursor-pointer select-none outline-none"
                @if (isset($joinedProjects) && !$joinedProjects->isEmpty()) onclick="toggleDropdown(event)">
                    <p>{{ $joinedProjects[0]->name ?? 'Project Name' }}</p>
                @else
                    <p>{{ 'No Project' }}</p> @endif
                <svg class="w-4 h-4 text-[#604B10] transition-transform duration-200 dropdown-icon" viewBox="0 0 24 24"
                fill="currentColor">
                <path
                    d="M5.25 8.5h13.5c.67 0 1.03.79.59 1.3l-6.75 7.78c-.33.38-.95.38-1.28 0L4.66 9.8c-.44-.51-.08-1.3.59-1.3z" />
                </svg>
            </button>

            <!-- The dropdown menu -->
            @if (isset($joinedProjects) && !$joinedProjects->isEmpty())
            <ul
                class="absolute top-[calc(100%+8px)] left-0 min-w-full bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] m-0 py-2 list-none z-50 hidden overflow-hidden dropdown-menu">
                @foreach($joinedProjects as $project)
                    <li>
                        <a href="?id={{ $project['id'] }}"
                            class="block px-6 py-2.5 text-[#604B10] no-underline text-[16px] font-medium transition-colors duration-150 hover:bg-[#FDCB40]">
                            {{ $project['name'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            @endif
        </div>
    </header>

    <!-- Main Content Slot -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

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
</body>

</html>
