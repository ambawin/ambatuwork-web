<x-app-layout>
    <x-slot name="title">AmbatuWork | Make everyone do their work!</x-slot>

    <!-- Hero Section -->
    <header class="max-w-4xl mx-auto px-6 py-32 text-center">
        <h1 class="text-5xl md:text-7xl font-black tracking-tight text-[#604B10] mb-8 leading-tight">
            Make everyone do<br>their work!
        </h1>
        <p class="text-xl text-[#977926] mb-12 max-w-2xl mx-auto leading-relaxed font-medium">
            A collaboration platform engineered to enforce accountability in student group projects. Track progress, assign roles, and evaluate contributions.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4" id="download">
            <a href="{{ route('dashboard') }}" class="bg-white text-[#604B10] px-8 py-4 rounded-full font-bold hover:bg-[#FDCB40] transition shadow-md">
                Launch Web App
            </a>
            <a href="#" class="bg-[#604B10] text-white px-8 py-4 rounded-full font-bold hover:bg-[#977926] transition shadow-md">
                Download for Android
            </a>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="bg-white/40 backdrop-blur-md border-y border-[#6E5003]/10 py-24">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                
                <!-- Feature 1 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm text-[#604B10]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Team Management</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        Centralized workspaces allow for clear role assignment and real-time contribution monitoring. This structural transparency ensures all members remain accountable for their assigned tasks throughout the project duration, leaving no room for ambiguity regarding responsibilities.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm text-[#604B10]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Sprint Execution</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        Assignments are divided into time-bound phases using agile sprint methodology. Progress is tracked systematically to force consistent output, identify early roadblocks, and eliminate the vulnerability of last-minute cramming by requiring verifiable deliverables at set intervals.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm text-[#604B10]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Peer Review</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        Integrated evaluation metrics generate verified participation scores at the end of each phase. This system provides concrete, exportable data revealing the exact contribution levels of every group member, ensuring grades align with actual work performed.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="max-w-6xl mx-auto px-6 py-12 text-sm text-[#977926] font-semibold">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <span>&copy; 2026 AmbatuWork. All rights reserved.</span>
            <div class="space-x-6 text-[#977926]">
                <a href="{{ route('dashboard') }}" class="hover:text-[#604B10] transition">Web App</a>
                <a href="#" class="hover:text-[#604B10] transition">Android</a>
            </div>
        </div>
    </footer>
</x-app-layout>