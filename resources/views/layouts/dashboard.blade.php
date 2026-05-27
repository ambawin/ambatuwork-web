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
            <ul class="absolute top-[calc(100%+8px)] left-0 min-w-full bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] m-0 p-2 list-none z-50 hidden dropdown-menu">
                @foreach($joinedProjects as $project)
                    <li>
                        <a href="?id={{ $project['id'] }}"
                            class="block px-6 py-2.5 text-[#604B10] no-underline text-[16px] font-medium transition-colors duration-150 rounded-full hover:bg-[#FDCB40]">
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

    <!-- Bottom Navigation -->
    <footer class="flex justify-center w-full mb-4">
        <div class="flex items-center justify-center gap-4 w-full">
            
            <!-- Profile
            <div class="w-14 h-14 aspect-square rounded-full border-2 border-white overflow-hidden shadow-md flex-shrink-0">
                <img src="https://t4.ftcdn.net/jpg/03/64/21/11/360_F_364211147_1qgLVxv1Tcq0Ohz3FawUfrtONzz8nq3e.jpg" alt="Profile" class="w-14 h-14 object-cover">
            </div> -->
            
            <div class="bg-white p-1.5 rounded-full flex items-center gap-1 shadow-md">
                <a href="#" class="bg-[#FDCB40] text-[#604B10] px-6 py-2.5 rounded-full flex items-center justify-center transition-colors duration-150">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                </a>
                <a href="#" class="text-[#604B10] px-6 py-2.5 rounded-full flex items-center justify-center transition-colors duration-150 hover:bg-gray-100">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zm3-12v3h14v-3H7zm0 13.5h14v-3H7v3zm0-4.5h14v-3H7v3z"/>
                    </svg>
                </a>

                <a href="#" class="text-[#604B10] px-6 py-2.5 rounded-full flex items-center justify-center transition-colors duration-150 hover:bg-gray-100">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4zm9 0a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-6zm0 8a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1h-6z"/>
                    </svg>
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
</body>

</html>
