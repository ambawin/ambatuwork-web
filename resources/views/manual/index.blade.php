<x-app-layout>
    <x-slot name="title">AmbatuWork | User Manual</x-slot>

    <div class="max-w-6xl mx-auto px-6 py-16 md:py-28 flex flex-col items-center text-center">
        <!-- Header -->
        <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-[#604B10] mb-4">
            User Manuals
        </h1>
        <p class="max-w-xl text-lg text-[#977926] mb-12 font-medium leading-relaxed">
            Select the platform you want to learn about. AmbatuWork helps student teams track work and enforce accountability using the SCRUM framework.
        </p>

        <!-- Selection Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-3xl">
            
            <!-- Web Manual Card -->
            <a href="{{ route('manual.web') }}" class="group flex flex-col justify-between bg-white/60 hover:bg-white/95 p-8 sm:p-10 rounded-3xl transition-all duration-300 transform hover:-translate-y-1.5 hover:shadow-xl text-left border border-white/40">
                <div>
                    <div class="h-14 w-14 bg-white group-hover:bg-[#FDCB40]/20 rounded-2xl flex items-center justify-center mb-6 text-[#604B10] transition-colors duration-300">
                        <x-heroicon-s-computer-desktop class="w-7 h-7"/>
                    </div>
                    <h2 class="text-2xl font-extrabold mb-3 text-[#604B10] group-hover:text-orange-600 transition-colors duration-300">
                        Web Version Manual
                    </h2>
                    <p class="text-[#6E5003]/90 leading-relaxed font-medium text-sm sm:text-base">
                        Learn how to create projects, configure the Definition of Done (DoD), run active sprint boards, submit daily standups, check-ins, retrospectives, and perform Peer Reviews.
                    </p>
                </div>
                <div class="mt-8 flex items-center gap-2 text-sm font-black text-[#604B10]">
                    <span>Read Web Guide</span>
                    <x-heroicon-s-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-1.5 duration-300"/>
                </div>
            </a>

            <!-- Android Manual Card (Coming Soon) -->
            <div class="relative overflow-hidden flex flex-col justify-between bg-white/30 p-8 sm:p-10 rounded-3xl text-left border border-white/10 opacity-75">
                <!-- Coming Soon Badge -->
                <span class="absolute top-4 right-4 bg-[#604B10]/10 text-[#604B10] text-xs font-black tracking-wide px-3 py-1.5 rounded-full uppercase">
                    Coming Soon
                </span>

                <div>
                    <div class="h-14 w-14 bg-white/50 rounded-2xl flex items-center justify-center mb-6 text-[#604B10]/40">
                        <x-heroicon-s-device-phone-mobile class="w-7 h-7"/>
                    </div>
                    <h2 class="text-2xl font-extrabold mb-3 text-[#604B10]/50">
                        Android App Manual
                    </h2>
                    <p class="text-[#6E5003]/60 leading-relaxed font-medium text-sm sm:text-base">
                        Access team project boards, submit daily standups and check-ins directly from your mobile device. Stay updated on assignments and track blocker notifications.
                    </p>
                </div>
                
                <div class="mt-8 flex items-center gap-2 text-sm font-bold text-[#6E5003]/40">
                    <span>Mobile Guide In Development</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
