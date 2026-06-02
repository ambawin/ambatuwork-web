<x-app-layout>
    <x-slot name="title">Pricing Plans | AmbatuWork</x-slot>

    <!-- Main Pricing Page Container with Interactive alpine states -->
    <div class="max-w-6xl mx-auto px-6 py-16 text-[#6E5003] antialiased selection:bg-yellow-500/20">
        
        <!-- Header Section -->
        <header class="text-center max-w-3xl mx-auto mb-16">
            <h1 class="text-4xl md:text-6xl font-black tracking-tight text-[#604B10] mb-6 leading-tight">
                Greatest investment<br/> for your team!
            </h1>
            <p class="text-lg md:text-xl text-[#977926] leading-relaxed font-medium">
                Looking for cheaper option? split the bills with your team members.
            </p>
        </header>

        <x-pricing.list />

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
