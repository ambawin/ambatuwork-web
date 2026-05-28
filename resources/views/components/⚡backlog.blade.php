<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $backlogItems;

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
            $this->backlogItems = $this->activeProject->backlogItems()
                ->orderBy('priority_rank', 'asc')
                ->with('assignedTo')
                ->get();
        } else {
            $this->backlogItems = collect();
        }
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Backlog</h1>
            <p class="text-sm text-[#876A1A] mt-1">
                @if ($activeProject)
                    Manage and prioritize backlog items for <span class="font-extrabold text-[#604B10]">{{ $activeProject->name }}</span>.
                @else
                    Select a project to view and manage its backlog items.
                @endif
            </p>
        </div>
        
        @if ($activeProject)
            <div>
                <button class="px-5 py-2.5 rounded-full bg-white text-[#604B10] text-sm font-extrabold flex items-center gap-1.5 cursor-pointer">
                    <x-heroicon-s-plus class="w-4 h-4"/>
                    Add Item
                </button>
            </div>
        @endif
    </div>

    <!-- Backlog Items List -->
    @if ($activeProject)
        @if (!$backlogItems->isEmpty())
            <div class="space-y-4">
                @foreach ($backlogItems as $item)
                    <div class="bg-white/85 backdrop-blur-md p-5 rounded-2xl border border-white/50 transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <!-- Left Info: Type and Title -->
                        <div class="flex items-start gap-3.5 flex-grow">
                            <!-- Type Indicator Badge/Icon -->
                            <div class="shrink-0 mt-0.5">
                                @if (strtolower($item->type) === 'bug')
                                    <span class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-600 border border-rose-500/20 flex items-center justify-center" title="Bug">
                                        <x-heroicon-s-bug-ant class="w-5 h-5"/>
                                    </span>
                                @elseif (strtolower($item->type) === 'chore')
                                    <span class="w-9 h-9 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20 flex items-center justify-center" title="Chore">
                                        <x-heroicon-s-cog-6-tooth class="w-5 h-5"/>
                                    </span>
                                @else
                                    <span class="w-9 h-9 rounded-xl bg-orange-500/10 text-orange-600 border border-orange-500/20 flex items-center justify-center" title="User Story">
                                        <x-heroicon-s-bookmark class="w-5 h-5"/>
                                    </span>
                                @endif
                            </div>
                            
                            <div>
                                <h3 class="font-extrabold text-[#604B10] text-base leading-snug">{{ $item->title }}</h3>
                                <p class="text-xs text-[#876A1A] mt-1 line-clamp-2 font-medium max-w-2xl">
                                    {{ $item->description ?: 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Info: Points, Value, Status, Assignee -->
                        <div class="flex flex-wrap items-center gap-3 shrink-0 md:justify-end">
                            <!-- Business Value -->
                            @if ($item->business_value)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20" title="Business Value">
                                    Val: {{ $item->business_value }}
                                </span>
                            @endif

                            <!-- Estimate Points -->
                            @if ($item->estimate_points)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-[#6E5003]/10 text-[#604B10] border border-[#6E5003]/20" title="Estimate Points">
                                    {{ $item->estimate_points }} pts
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-[#6E5003]/5 text-[#6E5003]/50 border border-[#6E5003]/10 italic">
                                    unestimated
                                </span>
                            @endif

                            <!-- Status Badge -->
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider 
                                @if(strtolower($item->status) === 'done') bg-green-500/10 text-green-700 border border-green-500/20
                                @elseif(strtolower($item->status) === 'in_progress') bg-orange-500/10 text-orange-700 border border-orange-500/20
                                @elseif(strtolower($item->status) === 'todo') bg-blue-500/10 text-blue-700 border border-blue-500/20
                                @elseif(strtolower($item->status) === 'qa') bg-purple-500/10 text-purple-700 border border-purple-500/20
                                @else bg-slate-500/10 text-slate-700 border border-slate-500/20 @endif">
                                {{ str_replace('_', ' ', $item->status) }}
                            </span>

                            <!-- Assignee Avatar -->
                            <div class="w-8 h-8 rounded-full bg-[#FDCB40]/20 text-[#604B10] font-black text-xs flex items-center justify-center border border-[#FDCB40]/40 overflow-hidden" title="{{ $item->assignedTo ? 'Assigned to: ' . $item->assignedTo->name : 'Unassigned' }}">
                                @if ($item->assignedTo)
                                    @if ($item->assignedTo->avatar_url)
                                        <img src="{{ $item->assignedTo->avatar_url }}" alt="{{ $item->assignedTo->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($item->assignedTo->name, 0, 2)) }}
                                    @endif
                                @else
                                    <x-heroicon-s-user class="w-4 h-4 text-[#876A1A]"/>
                                @endif
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty Backlog -->
            <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl border border-white/50 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                    <x-heroicon-s-numbered-list class="w-8 h-8"/>
                </div>
                <h3 class="text-xl font-black text-[#604B10]">Your Backlog is Empty</h3>
                <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                    Start by adding items to plan tasks, user stories, or track bugs!
                </p>
                <div class="pt-2">
                    <button class="inline-flex px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition-colors cursor-pointer flex items-center justify-center gap-2">
                        <x-heroicon-s-plus class="w-4 h-4"/>
                        Add Your First Item
                    </button>
                </div>
            </div>
        @endif
    @else
        <!-- No project chosen -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl border border-white/50 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-folder-open class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Project Selected</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                Please select a project from the dropdown at the top of the page to view its backlog.
            </p>
        </div>
    @endif
</div>
