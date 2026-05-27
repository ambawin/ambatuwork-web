<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $sprints;

    public function mount()
    {
        $activeProjectId = request()->query('project_id') ?: session('active_project_id');
        $user = Auth::user();

        if ($activeProjectId) {
            $this->activeProject = \App\Models\Project::visibleTo($user)
                ->find($activeProjectId);
        }

        if (!$this->activeProject) {
            $allProjects = \App\Models\Project::visibleTo($user)
                ->latest()
                ->get();
            if (!$allProjects->isEmpty()) {
                $this->activeProject = $allProjects->first();
                session(['active_project_id' => $this->activeProject->id]);
            }
        }

        if ($this->activeProject) {
            $this->sprints = $this->activeProject->sprints()
                ->withCount('items')
                ->orderBy('status', 'asc') // Active and planned first, closed later
                ->orderBy('start_date', 'desc')
                ->with('createdBy')
                ->get();
        } else {
            $this->sprints = collect();
        }
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Sprints</h1>
            <p class="text-sm text-[#876A1A] mt-1">
                @if ($activeProject)
                    View and plan sprints for <span class="font-extrabold text-[#604B10]">{{ $activeProject->name }}</span>.
                @else
                    Select a project to view its sprint timeline and boards.
                @endif
            </p>
        </div>
        
        @if ($activeProject)
            <div>
                <button class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold shadow-sm hover:shadow-md hover:bg-[#FDCB40]/90 transition-all duration-150 flex items-center gap-1.5 cursor-pointer">
                    <x-heroicon-s-plus class="w-4 h-4"/>
                    Create Sprint
                </button>
            </div>
        @endif
    </div>

    <!-- Sprints List -->
    @if ($activeProject)
        @if (!$sprints->isEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($sprints as $sprint)
                    <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-white/50 hover:shadow-md transition-all duration-200 flex flex-col justify-between h-full relative overflow-hidden">
                        
                        <!-- Top status ribbon/accent -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 
                            @if(strtolower($sprint->status) === 'active') bg-green-500
                            @elseif(strtolower($sprint->status) === 'planned') bg-blue-500
                            @else bg-slate-400 @endif">
                        </div>

                        <div>
                            <!-- Header: Name & Status -->
                            <div class="flex items-center justify-between gap-4 mb-4 mt-1">
                                <h3 class="font-black text-[#604B10] text-lg leading-tight tracking-tight">
                                    {{ $sprint->name }}
                                </h3>

                                <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider 
                                    @if(strtolower($sprint->status) === 'active') bg-green-500/10 text-green-700 border border-green-500/20
                                    @elseif(strtolower($sprint->status) === 'planned') bg-blue-500/10 text-blue-700 border border-blue-500/20
                                    @else bg-slate-500/10 text-slate-700 border border-slate-500/20 @endif">
                                    @if(strtolower($sprint->status) === 'active')
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-ping"></span>
                                    @endif
                                    {{ $sprint->status }}
                                </span>
                            </div>

                            <!-- Dates -->
                            <div class="flex items-center gap-1.5 text-xs text-[#876A1A] font-bold mb-3">
                                <x-heroicon-s-calendar class="w-4 h-4 text-[#876A1A]"/>
                                <span>{{ $sprint->start_date->format('M d, Y') }} — {{ $sprint->end_date->format('M d, Y') }}</span>
                            </div>

                            <!-- Sprint Goal -->
                            @if ($sprint->sprint_goal)
                                <div class="text-sm text-[#6E5003] font-medium leading-relaxed mb-6 bg-[#FDCB40]/5 p-3.5 rounded-2xl border border-[#FDCB40]/15">
                                    <span class="text-[10px] font-bold text-[#876A1A] uppercase tracking-wider block mb-1">Sprint Goal</span>
                                    {{ $sprint->sprint_goal }}
                                </div>
                            @endif
                        </div>

                        <!-- Footer Info & Button -->
                        <div class="flex items-center justify-between border-t border-[#6E5003]/10 pt-4 mt-auto">
                            <!-- Metrics -->
                            <div class="text-xs text-[#876A1A] font-extrabold flex items-center gap-1">
                                <x-heroicon-s-numbered-list class="w-4 h-4"/>
                                <span>{{ $sprint->items_count }} items committed</span>
                            </div>

                            <!-- Action -->
                            <button class="inline-flex items-center gap-1 px-4 py-1.5 rounded-full text-xs font-extrabold transition-all duration-150 border cursor-pointer
                                @if(strtolower($sprint->status) === 'active') bg-[#FDCB40] text-[#604B10] border-[#FDCB40] hover:bg-[#FDCB40]/90
                                @else bg-white text-[#604B10] border-[#6E5003]/20 hover:bg-[#FDCB40]/10 @endif">
                                Enter Board
                                <x-heroicon-s-chevron-right class="w-3.5 h-3.5"/>
                            </button>
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty Sprints -->
            <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl shadow-lg border border-white/50 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                    <x-heroicon-s-calendar class="w-8 h-8"/>
                </div>
                <h3 class="text-xl font-black text-[#604B10]">No Sprints Yet</h3>
                <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                    Sprints help teams prioritize their product goal into highly-focused, short periods. Create your first sprint to start planning work!
                </p>
                <div class="pt-2">
                    <button class="inline-flex px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold shadow-sm hover:shadow-md hover:bg-[#FDCB40]/90 transition-colors">
                        Create Your First Sprint
                    </button>
                </div>
            </div>
        @endif
    @else
        <!-- No project chosen -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl shadow-lg border border-white/50 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-folder-open class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Project Selected</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                Please select a project from the dropdown at the top of the page to view its sprints.
            </p>
        </div>
    @endif
</div>
