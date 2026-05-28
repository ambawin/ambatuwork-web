<x-app-layout>
    <x-slot name="title">Pricing Plans | AmbatuWork</x-slot>

    <!-- Main Pricing Page Container with Interactive alpine states -->
    <div x-data="{ billingPeriod: 'monthly' }" class="max-w-6xl mx-auto px-6 py-16 text-[#6E5003] antialiased selection:bg-yellow-500/20">
        
        <!-- Header Section -->
        <header class="text-center max-w-3xl mx-auto mb-16">
            <h1 class="text-4xl md:text-6xl font-black tracking-tight text-[#604B10] mb-6 leading-tight">
                Simple, transparent<br>pricing
            </h1>
            <p class="text-lg md:text-xl text-[#977926] leading-relaxed font-medium">
                Enforce accountability, coordinate tasks, and keep your project groups on track. Choose the plan that best fits your workflow.
            </p>

            <!-- Interactive Billing Switcher -->
            <div class="mt-10 inline-flex items-center justify-center bg-white/40 backdrop-blur-md p-1.5 rounded-full border border-[#6E5003]/10 shadow-inner">
                <button 
                    @click="billingPeriod = 'monthly'"
                    :class="billingPeriod === 'monthly' ? 'bg-[#604B10] text-white shadow-md' : 'text-[#604B10] hover:text-[#977926]'"
                    class="px-6 py-2.5 rounded-full text-sm font-bold tracking-wide transition duration-300 ease-in-out cursor-pointer"
                >
                    Monthly Billing
                </button>
                <button 
                    @click="billingPeriod = 'yearly'"
                    :class="billingPeriod === 'yearly' ? 'bg-[#604B10] text-white shadow-md' : 'text-[#604B10] hover:text-[#977926]'"
                    class="px-6 py-2.5 rounded-full text-sm font-bold tracking-wide transition duration-300 ease-in-out flex items-center gap-2 cursor-pointer"
                >
                    Yearly Billing
                    <span class="bg-[#FDCB40] text-[#604B10] text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider animate-pulse shadow-sm">
                        Save ~20%
                    </span>
                </button>
            </div>
        </header>

        <!-- Pricing Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch mb-24">
            
            <!-- FREE PLAN CARD -->
            <div class="bg-white/50 backdrop-blur-md border border-white/40 rounded-3xl p-8 flex flex-col shadow-[0_10px_30px_rgba(0,0,0,0.02)] transition hover:translate-y-[-4px] hover:shadow-[0_15px_35px_rgba(0,0,0,0.04)] duration-300">
                <div class="mb-6">
                    <h3 class="text-xl font-black text-[#604B10] mb-2 uppercase tracking-wider">Free</h3>
                    <p class="text-sm font-medium text-[#977926] min-h-[40px]">Perfect for individuals and small group projects getting started.</p>
                </div>

                <!-- Price Block -->
                <div class="mb-8">
                    <div class="flex items-baseline">
                        <span class="text-4xl font-black text-[#604B10] tracking-tight">Rp 0</span>
                        <span class="text-[#977926] font-semibold text-sm ml-2">/ month</span>
                    </div>
                    <p class="text-xs text-[#977926]/70 mt-1 font-medium">Free forever, no credit card required.</p>
                </div>

                <!-- Feature List -->
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">1 Active Workspace</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Up to 5 Team Members</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Basic Sprint Board</span>
                    </li>
                    <li class="flex items-start gap-3 text-[#977926]/60">
                        <svg class="w-5 h-5 text-[#6E5003]/30 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="text-sm font-medium">Verified Peer Evaluation</span>
                    </li>
                    <li class="flex items-start gap-3 text-[#977926]/60">
                        <svg class="w-5 h-5 text-[#6E5003]/30 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="text-sm font-medium">Exportable PDF/Excel Reports</span>
                    </li>
                </ul>

                <!-- Button -->
                <a href="{{ route('login') }}" class="block w-full text-center bg-white hover:bg-[#FDCB40] text-[#604B10] font-bold py-3.5 px-6 rounded-full transition shadow-sm border border-[#6E5003]/10">
                    Get Started
                </a>
            </div>

            <!-- STUDENT PLAN CARD (MOST POPULAR / PREMIUM FEATURED) -->
            <div class="bg-white rounded-3xl p-8 flex flex-col shadow-[0_15px_40px_rgba(96,75,16,0.08)] border-2 border-[#604B10]/40 relative transition hover:translate-y-[-4px] duration-300">
                <div class="absolute top-0 right-1/2 translate-x-1/2 translate-y-[-50%] bg-[#604B10] text-[#FDCB40] text-[11px] font-black tracking-widest px-4 py-1.5 rounded-full uppercase shadow">
                    Most Popular
                </div>

                <div class="mb-6 mt-2">
                    <h3 class="text-xl font-black text-[#604B10] mb-2 uppercase tracking-wider">Student</h3>
                    <p class="text-sm font-medium text-[#977926] min-h-[40px]">Designed specifically for student groups needing rigorous team accountability.</p>
                </div>

                <!-- Price Block -->
                <div class="mb-8">
                    <div class="flex items-baseline">
                        <!-- Dynamic prices with custom Alpine values -->
                        <span class="text-4xl font-black text-[#604B10] tracking-tight" 
                              x-text="billingPeriod === 'monthly' ? 'Rp 19.000' : 'Rp 15.000'">
                            Rp 19.000
                        </span>
                        <span class="text-[#977926] font-semibold text-sm ml-2">/ month</span>
                    </div>
                    <!-- Dynamic billed yearly label -->
                    <p class="text-xs text-[#977926] mt-1 font-semibold min-h-[16px]">
                        <span x-show="billingPeriod === 'yearly'" style="display: none;">Billed Rp 180.000 / year</span>
                        <span x-show="billingPeriod === 'monthly'">&nbsp;</span>
                    </p>
                </div>

                <!-- Feature List -->
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">5 Active Workspaces</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Up to 15 Members / Group</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Advanced Sprint Boards</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Verified Peer Evaluations</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Export PDF/Excel (For Grading)</span>
                    </li>
                </ul>

                <!-- Button -->
                <a href="{{ route('login') }}" class="block w-full text-center bg-[#604B10] hover:bg-[#977926] text-white font-bold py-3.5 px-6 rounded-full transition shadow-md">
                    Claim Student Access
                </a>
            </div>

            <!-- PROFESSIONAL PLAN CARD -->
            <div class="bg-white/50 backdrop-blur-md border border-white/40 rounded-3xl p-8 flex flex-col shadow-[0_10px_30px_rgba(0,0,0,0.02)] transition hover:translate-y-[-4px] hover:shadow-[0_15px_35px_rgba(0,0,0,0.04)] duration-300">
                <div class="mb-6">
                    <h3 class="text-xl font-black text-[#604B10] mb-2 uppercase tracking-wider">Professional</h3>
                    <p class="text-sm font-medium text-[#977926] min-h-[40px]">For professional engineers, labs, and organizations demanding advanced workspace controls.</p>
                </div>

                <!-- Price Block -->
                <div class="mb-8">
                    <div class="flex items-baseline">
                        <!-- Dynamic prices with custom Alpine values -->
                        <span class="text-4xl font-black text-[#604B10] tracking-tight" 
                              x-text="billingPeriod === 'monthly' ? 'Rp 49.000' : 'Rp 39.000'">
                            Rp 49.000
                        </span>
                        <span class="text-[#977926] font-semibold text-sm ml-2">/ month</span>
                    </div>
                    <!-- Dynamic billed yearly label -->
                    <p class="text-xs text-[#977926] mt-1 font-semibold min-h-[16px]">
                        <span x-show="billingPeriod === 'yearly'" style="display: none;">Billed Rp 468.000 / year</span>
                        <span x-show="billingPeriod === 'monthly'">&nbsp;</span>
                    </p>
                </div>

                <!-- Feature List -->
                <ul class="space-y-4 mb-8 flex-grow">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Unlimited Workspaces</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Unlimited Team Members</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Customizable Agile Workflows</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">Detailed Accountability Auditing</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#604B10] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-[#6E5003]/90">API Access & Slack Integration</span>
                    </li>
                </ul>

                <!-- Button -->
                <a href="{{ route('login') }}" class="block w-full text-center bg-white hover:bg-[#FDCB40] text-[#604B10] font-bold py-3.5 px-6 rounded-full transition shadow-sm border border-[#6E5003]/10">
                    Get Professional
                </a>
            </div>

        </div>

        <!-- PLAN COMPARISON MATRIX -->
        <section class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-2xl md:text-4xl font-black text-[#604B10] mb-4">Compare all features</h2>
                <p class="text-sm md:text-base font-semibold text-[#977926]">
                    Get a side-by-side view of the specific capacities included in every option.
                </p>
            </div>

            <!-- Responsive Table/Grid Structure -->
            <div class="bg-white/40 backdrop-blur-md border border-[#6E5003]/10 rounded-3xl overflow-hidden shadow-sm">
                
                <!-- Table Header (Desktop Only View) -->
                <div class="hidden md:grid grid-cols-4 bg-[#604B10] text-[#FDCB40] px-8 py-5 text-sm font-black uppercase tracking-wider">
                    <div>Features</div>
                    <div class="text-center">Free</div>
                    <div class="text-center">Student</div>
                    <div class="text-center">Professional</div>
                </div>

                <!-- Features Category: Workspaces & Members -->
                <div class="border-b border-[#6E5003]/10">
                    <div class="px-8 py-3 bg-[#604B10]/5 text-xs font-bold uppercase tracking-wider text-[#604B10]">
                        Workspace Limits
                    </div>

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0 border-b border-[#6E5003]/5">
                        <div class="font-bold text-[#604B10]">Active Workspaces</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span>1 Workspace</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span>5 Workspaces</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold text-[#604B10] font-black">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Unlimited</span>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0">
                        <div class="font-bold text-[#604B10]">Members per Workspace</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span>5 Members</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span>15 Members</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold text-[#604B10] font-black">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Unlimited</span>
                        </div>
                    </div>
                </div>

                <!-- Features Category: Agile Sprint Board -->
                <div class="border-b border-[#6E5003]/10">
                    <div class="px-8 py-3 bg-[#604B10]/5 text-xs font-bold uppercase tracking-wider text-[#604B10]">
                        Project Management & Boards
                    </div>

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0 border-b border-[#6E5003]/5">
                        <div class="font-bold text-[#604B10]">Sprint Management</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span>Basic Boards</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span>Advanced Backlog & Boards</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Fully Customizable Board Flows</span>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0">
                        <div class="font-bold text-[#604B10]">Sprint History Retention</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span>7 Days</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span>Unlimited</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Unlimited</span>
                        </div>
                    </div>
                </div>

                <!-- Features Category: Accountability -->
                <div>
                    <div class="px-8 py-3 bg-[#604B10]/5 text-xs font-bold uppercase tracking-wider text-[#604B10]">
                        Accountability & Audits
                    </div>

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0 border-b border-[#6E5003]/5">
                        <div class="font-bold text-[#604B10]">Peer Evaluation System</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span class="text-[#6E5003]/30">Not Available</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span>Standard Verified Reviews</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Interactive Review Analytics</span>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0 border-b border-[#6E5003]/5">
                        <div class="font-bold text-[#604B10]">Grading Export Reports</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span class="text-[#6E5003]/30">Not Available</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span>PDF & Excel Exports</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Advanced Analytics Dashboards</span>
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 px-8 py-4 items-center gap-2 md:gap-0">
                        <div class="font-bold text-[#604B10]">Team Audit Trails</div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Free:</span>
                            <span class="text-[#6E5003]/30">Not Available</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Student:</span>
                            <span class="text-[#6E5003]/30">Not Available</span>
                        </div>
                        <div class="flex md:justify-center justify-between text-sm font-semibold">
                            <span class="md:hidden text-[#977926] font-bold">Professional:</span>
                            <span>Full Compliance Logging</span>
                        </div>
                    </div>
                </div>

            </div>
        </section>

    </div>
</x-app-layout>
