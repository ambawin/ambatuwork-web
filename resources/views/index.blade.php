<x-app-layout>
    <x-slot name="title">AmbatuWork | Make everyone do their work!</x-slot>

    <header class="flex flex-col-reverse md:flex-row items-center justify-between gap-12 max-w-6xl mx-auto px-6 py-16 md:py-32 text-left">
        <!-- Greeting -->
        <div class="w-full md:w-1/2">
            <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-[#604B10] mb-6 leading-tight">
                Make everyone do<br class="hidden sm:inline"> their work!
            </h1>
            <p class="max-w-lg text-lg text-[#977926] mb-8 leading-relaxed font-medium">
                A collaboration platform engineered to enforce accountability in student group projects. Track progress, assign roles, and evaluate contributions.
            </p>
            <div class="flex flex-col sm:flex-row justify-start gap-4 mb-4" id="continue">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-block bg-white text-[#604B10] px-8 py-4 rounded-full font-bold hover:bg-white/70 transition text-center">
                    @auth
                        Go to dashboard                        
                    @else
                        Get started
                    @endauth
                </a>
            </div>
            <p class="max-w-md text-sm text-[#977926] leading-relaxed font-medium pl-2">
                @auth
                    You already logged in, 
                @else
                    New here? let's get started 
                @endauth
                <a href="https://github.com/ambawin/ambatuwork-android" class="font-bold underline">
                    @auth
                        go to Dashboard
                    @else
                        for FREE
                    @endauth
                </a>
            </p>
        </div>

        <!-- Image -->
        <div class="w-full md:w-1/2 flex items-center justify-center">
            <img src="{{ asset('images/ambatuwork.png') }}" alt="hero image" class="w-48 sm:w-64 md:w-full md:max-w-md h-auto object-contain">
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="flex flex-col gap-8 py-8 max-w-7xl mx-auto rounded-4xl mt-16">
        <h2 class="text-5xl font-black tracking-tight text-[#604B10] leading-tight text-center">
            Just. do. the. WORK!
        </h2>
        <p class="px-2 max-w-2xl text-lg text-[#977926] text-center mx-auto leading-relaxed font-medium mb-4">
            Using the <a href="https://www.scrum.org/resources/what-scrum-module" target="_blank" class="underline font-bold">SCRUM</a> framework,<br/>we aim to simplify your workflow into three steps.
        </p>
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-10">
                <!-- Feature 1 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left bg-white/60 p-8 rounded-3xl">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 text-[#604B10]">
                        <x-heroicon-s-user-group class="w-6 h-6"/>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Backlogs</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        List of all the deliverables you gotta work on.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left bg-white/60 p-8 rounded-3xl">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 text-[#604B10]">
                        <x-heroicon-s-bolt class="w-6 h-6"/>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Sprints</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        Which backlog we could do in X-days? (typically 7/14 days)
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left bg-white/60 p-8 rounded-3xl">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 text-[#604B10]">
                        <x-heroicon-s-document-check class="w-6 h-6"/>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Peer Review</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        How well the team perform?
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- Keynote --}}
    <section class="flex flex-col gap-8 items-center justify-center w-full mt-24">
        <h2 class="px-4 text-5xl font-black tracking-tight text-[#604B10] mb-6 leading-tight text-center">
            Watch the keynote
        </h2>
        <div class="w-full max-w-4xl px-6 md:px-12">
            <video 
                class="w-full aspect-video rounded-2xl shadow-lg border border-black/10" 
                src="{{ asset('storage/videos/keynote-web.mp4') }}" 
                controls>
                Your browser does not support the video tag.
            </video>
        </div>
    </section>

    {{-- Get Started --}}
    <section class="max-w-6xl mx-auto px-6 flex flex-col justify-center gap-8 mb-16 mt-16">
        <h2 class="text-5xl font-black tracking-tight text-[#604B10] mb-6 leading-tight text-center">
            Start delivering today!
        </h2>
        <div class="flex flex-col sm:flex-row justify-center gap-4 mb-4" id="continue">
            <a href="{{ route('login') }}" class="inline-block bg-white text-[#604B10] px-8 py-4 rounded-full font-bold hover:bg-white/70 transition text-center">
                @auth
                    Go to dashboard                   
                @else
                    Get started for FREE
                @endauth
            </a>
        </div>
    </section>

</x-app-layout>