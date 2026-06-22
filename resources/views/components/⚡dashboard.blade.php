<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $user;
    public $activeProject;
    public $stats = null;

    public function mount()
    {
        $this->user = Auth::user();

        // Active project switching
        $activeProjectId = request()->query('project_id') ?: session('active_project_id');
        if ($activeProjectId) {
            $this->activeProject = \App\Models\Project::visibleTo($this->user)
                ->with(['owner', 'activeSprint'])
                ->find($activeProjectId);
        }
        
        if (!$this->activeProject) {
            $allProjects = \App\Models\Project::visibleTo($this->user)
                ->with(['owner', 'activeSprint'])
                ->latest()
                ->get();
            if (!$allProjects->isEmpty()) {
                $this->activeProject = $allProjects->first();
                session(['active_project_id' => $this->activeProject->id]);
            }
        }

        if ($this->activeProject) {
            try {
                $statsController = new \App\Http\Controllers\Api\V1\ProjectStatsController();
                $this->stats = $statsController->show(request(), $this->activeProject)->resolve();
            } catch (\Exception $e) {
                // If authorization fails or project not found, keep stats null
                $this->stats = null;
            }
        }
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- User Greeting -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-4xl font-extrabold text-[#604B10] tracking-tight">Hello, {{ explode(' ', $user->name)[0] }}!</h1>
            <p class="text-sm text-[#876A1A] mt-1">Here is the latest statistics overview for your active project.</p>
        </div>
    </div>

    @if ($activeProject && $stats)
        <!-- Grid: 4 Top Vitals Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Average Velocity -->
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] transition-all duration-300 hover:shadow-md hover:translate-y-[-2px]">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Avg Velocity</h3>
                    <div class="p-2 rounded-xl bg-[#604B10]/10 text-[#604B10]">
                        <x-heroicon-s-bolt class="w-5 h-5"/>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-[#604B10]">{{ $stats['sprints']['average_velocity'] }}</span>
                    <span class="text-xs text-[#876A1A] font-semibold">pts/sprint</span>
                </div>
                <div class="mt-2 text-xs text-[#876A1A]/70 font-medium">
                    Calculated over {{ $stats['sprints']['completed'] }} closed sprints
                </div>
            </div>

            <!-- Team Happiness -->
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] transition-all duration-300 hover:shadow-md hover:translate-y-[-2px]">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Team Happiness</h3>
                    <div class="p-2 rounded-xl bg-[#604B10]/10 text-[#604B10]">
                        <x-heroicon-s-face-smile class="w-5 h-5"/>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-[#604B10]">{{ $stats['retrospectives']['average_happiness_score'] }}</span>
                    <span class="text-xs text-[#876A1A] font-semibold">/ 5.0</span>
                </div>
                <div class="mt-2 text-xs text-[#876A1A]/70 font-medium">
                    From {{ $stats['retrospectives']['total'] }} retrospective sessions
                </div>
            </div>

            <!-- Team Confidence -->
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] transition-all duration-300 hover:shadow-md hover:translate-y-[-2px]">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Avg Confidence</h3>
                    <div class="p-2 rounded-xl bg-[#604B10]/10 text-[#604B10]">
                        <x-heroicon-s-chart-bar class="w-5 h-5"/>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-[#604B10]">{{ $stats['daily_checkins']['average_confidence'] }}</span>
                    <span class="text-xs text-[#876A1A] font-semibold">/ 5.0</span>
                </div>
                <div class="mt-2 text-xs text-[#876A1A]/70 font-medium">
                    Across {{ $stats['daily_checkins']['total_submitted'] }} daily check-ins
                </div>
            </div>

            <!-- Impediments Status -->
            <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] transition-all duration-300 hover:shadow-md hover:translate-y-[-2px]">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Impediments</h3>
                    @php
                        $openImpediments = ($stats['impediments']['by_status']['open'] ?? 0) + ($stats['impediments']['by_status']['in_progress'] ?? 0);
                    @endphp
                    <div class="p-2 rounded-xl {{ $openImpediments > 0 ? 'bg-[#604B10] text-white animate-pulse' : 'bg-[#604B10]/10 text-[#604B10]' }}">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5"/>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-[#604B10]">{{ $openImpediments }}</span>
                    <span class="text-xs text-[#876A1A] font-semibold">active</span>
                </div>
                <div class="mt-2 text-xs text-[#876A1A]/70 font-medium">
                    {{ $stats['impediments']['resolved'] }} of {{ $stats['impediments']['total'] }} resolved
                </div>
            </div>
        </div>

        <!-- Main Grid: 2 Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Side: Backlog Items Progress & Sprints -->
            <div class="space-y-8">
                <!-- Backlog Progress Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)]">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6">
                        <x-heroicon-s-numbered-list class="w-5 h-5 text-[#604B10]"/>
                        Backlog Points & Completion
                    </h3>

                    @php
                        $totalPoints = $stats['backlog_items']['total_points'];
                        $completedPoints = $stats['backlog_items']['completed_points'];
                        $completionPercentage = $totalPoints > 0 ? round(($completedPoints / $totalPoints) * 100, 1) : 0;
                    @endphp

                    <div class="space-y-4">
                        <div class="flex justify-between items-baseline">
                            <div>
                                <span class="text-3xl font-black text-[#604B10]">{{ $completedPoints }}</span>
                                <span class="text-sm font-semibold text-[#876A1A]">/ {{ $totalPoints }} points completed</span>
                            </div>
                            <span class="text-lg font-black text-[#604B10]">{{ $completionPercentage }}%</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-[#6E5003]/10 rounded-full h-3.5 overflow-hidden">
                            <div class="bg-[#FDCB40] h-full rounded-full transition-all duration-500" style="width: {{ $completionPercentage }}%"></div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-6 border-t border-[#6E5003]/5">
                            <div class="p-3 bg-[#6E5003]/5 rounded-xl text-center">
                                <span class="block text-xs font-bold text-[#876A1A] uppercase tracking-wider">Total Items</span>
                                <span class="text-xl font-extrabold text-[#604B10] mt-1 block">{{ $stats['backlog_items']['total'] }}</span>
                            </div>
                            <div class="p-3 bg-[#604B10]/5 rounded-xl text-center">
                                <span class="block text-xs font-bold text-[#876A1A] uppercase tracking-wider">In Progress</span>
                                <span class="text-xl font-extrabold text-[#604B10] mt-1 block">{{ $stats['backlog_items']['by_status']['in_progress'] ?? 0 }}</span>
                            </div>
                            <div class="p-3 bg-[#FDCB40]/10 rounded-xl text-center">
                                <span class="block text-xs font-bold text-[#604B10] uppercase tracking-wider">Done Items</span>
                                <span class="text-xl font-extrabold text-[#604B10] mt-1 block">{{ $stats['backlog_items']['by_status']['done'] ?? 0 }}</span>
                            </div>
                        </div>

                        <!-- Full Breakdown list of states -->
                        <div class="pt-4 space-y-2">
                            <h4 class="text-xs font-extrabold text-[#876A1A] uppercase tracking-wider mb-2">Item Status Breakdown</h4>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($stats['backlog_items']['by_status'] as $status => $count)
                                    <div class="flex items-center justify-between px-3 py-2 bg-white/50 rounded-xl shadow-sm">
                                        <span class="text-xs font-bold text-[#876A1A] capitalize">{{ str_replace('_', ' ', $status) }}</span>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-[#604B10] bg-[#604B10]/10 px-2 py-0.5 rounded-full">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sprint Stats Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)]">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-5">
                        <x-heroicon-s-arrow-path class="w-5 h-5 text-[#876A1A]"/>
                        Sprints History
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 bg-white/50 rounded-2xl text-center shadow-sm">
                            <span class="text-xs font-bold text-[#876A1A] uppercase block">Total</span>
                            <span class="text-3xl font-extrabold text-[#604B10] mt-1 block">{{ $stats['sprints']['total'] }}</span>
                        </div>
                        <div class="p-4 bg-[#FDCB40]/10 rounded-2xl text-center">
                            <span class="text-xs font-bold text-[#876A1A] uppercase block">Active</span>
                            <span class="text-3xl font-extrabold text-[#604B10] mt-1 block">{{ $stats['sprints']['active'] }}</span>
                        </div>
                        <div class="p-4 bg-[#604B10]/5 rounded-2xl text-center">
                            <span class="text-xs font-bold text-[#876A1A] uppercase block">Completed</span>
                            <span class="text-3xl font-extrabold text-[#604B10] mt-1 block">{{ $stats['sprints']['completed'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Peer Reviews & Team Demographics -->
            <div class="space-y-8">
                <!-- Peer Reviews Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)]">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6">
                        <x-heroicon-s-star class="w-5 h-5 text-[#FDCB40]"/>
                        Peer Review Scores
                    </h3>

                    <div class="space-y-5">
                        <div class="flex justify-between items-baseline mb-2">
                            <span class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Total Cycles Executed</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-[10px] font-black uppercase tracking-wider">{{ $stats['peer_reviews']['total_cycles'] }}</span>
                        </div>

                        <!-- Collaboration Score -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="font-bold text-[#604B10] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#604B10]"></span>
                                    Collaboration
                                </span>
                                <span class="font-extrabold text-[#604B10]">{{ $stats['peer_reviews']['average_scores']['collaboration'] }} / 5.0</span>
                            </div>
                            <div class="w-full bg-[#6E5003]/10 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-[#604B10] h-full rounded-full transition-all duration-300" style="width: {{ round(($stats['peer_reviews']['average_scores']['collaboration'] / 5) * 100, 2) }}%"></div>
                            </div>
                        </div>

                        <!-- Delivery Score -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="font-bold text-[#604B10] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#FDCB40]"></span>
                                    Delivery
                                </span>
                                <span class="font-extrabold text-[#604B10]">{{ $stats['peer_reviews']['average_scores']['delivery'] }} / 5.0</span>
                            </div>
                            <div class="w-full bg-[#6E5003]/10 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-[#FDCB40] h-full rounded-full transition-all duration-300" style="width: {{ round(($stats['peer_reviews']['average_scores']['delivery'] / 5) * 100, 2) }}%"></div>
                            </div>
                        </div>

                        <!-- Communication Score -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="font-bold text-[#604B10] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#876A1A]"></span>
                                    Communication
                                </span>
                                <span class="font-extrabold text-[#604B10]">{{ $stats['peer_reviews']['average_scores']['communication'] }} / 5.0</span>
                            </div>
                            <div class="w-full bg-[#6E5003]/10 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-[#876A1A] h-full rounded-full transition-all duration-300" style="width: {{ round(($stats['peer_reviews']['average_scores']['communication'] / 5) * 100, 2) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Demographics/Roles Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)]">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-5">
                        <x-heroicon-s-users class="w-5 h-5 text-[#876A1A]"/>
                        Team Composition
                    </h3>
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="text-4xl font-black text-[#604B10]">{{ $stats['members']['total'] }}</span>
                        <span class="text-sm font-bold text-[#876A1A] uppercase tracking-wider">Total Active Users</span>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-white/50 rounded-2xl shadow-sm">
                            <span class="text-sm font-bold text-[#604B10] flex items-center gap-2">
                                <x-heroicon-s-key class="w-4 h-4 text-[#604B10]"/>
                                Product Owner
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-[10px] font-black uppercase tracking-wider">
                                {{ $stats['members']['by_role']['owner'] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/50 rounded-2xl shadow-sm">
                            <span class="text-sm font-bold text-[#604B10] flex items-center gap-2">
                                <x-heroicon-s-eye class="w-4 h-4 text-[#604B10]"/>
                                Supervisors
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-[10px] font-black uppercase tracking-wider">
                                {{ $stats['members']['by_role']['supervisor'] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/50 rounded-2xl shadow-sm">
                            <span class="text-sm font-bold text-[#604B10] flex items-center gap-2">
                                <x-heroicon-s-academic-cap class="w-4 h-4 text-[#604B10]"/>
                                Developers/Members
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#FDCB40] text-[#604B10] text-[10px] font-black uppercase tracking-wider">
                                {{ $stats['members']['by_role']['member'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- No Project Selected / Empty State -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl text-center space-y-4 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-folder-open class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Project Selected</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                You don't have any projects yet or haven't selected one. Create a project to start tracking statistics!
            </p>
        </div>
    @endif
</div>