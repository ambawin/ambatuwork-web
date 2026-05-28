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
                <a href="{{ route('pricing') }}" class="inline-block bg-white text-[#604B10] px-8 py-4 rounded-full font-bold hover:bg-white/70 transition text-center">
                    @auth
                        Go to dashboard                        
                    @else
                        See plans & pricing
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
    <section id="features" class="bg-white/40 backdrop-blur-md border-y border-[#6E5003]/10 py-24 max-w-7xl mx-auto rounded-4xl">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                
                <!-- Feature 1 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 text-[#604B10]">
                        <x-heroicon-s-user-group class="w-6 h-6"/>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Backlogs</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        List of all the deliverables you gotta work on.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center mb-6 text-[#604B10]">
                        <x-heroicon-s-bolt class="w-6 h-6"/>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-[#604B10]">Sprints</h3>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium">
                        Which backlog we could do in X-days? (typically 7/14 days)
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="flex flex-col items-center md:items-start text-center md:text-left">
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

    <section class="max-w-6xl mx-auto px-6 py-16">
        <x-pricing.list />
    </section>

</x-app-layout>