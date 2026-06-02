<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\ProjectInvitation;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $user;
    public $ownedProjects;
    public $joinedProjects;
    public $pendingInvitations;
    public $totalProjectsCount;
    public $activeSprintsCount;
    public $activeProject;

    public function mount()
    {
        $this->user = Auth::user();

        $this->ownedProjects = $this->user->ownedProjects()
            ->with(['members', 'activeSprint'])
            ->latest()
            ->get();

        $this->joinedProjects = $this->user->projects()
            ->with(['owner', 'activeSprint'])
            ->latest()
            ->get();

        $this->pendingInvitations = ProjectInvitation::query()
            ->where('email', $this->user->email)
            ->where('status', 'pending')
            ->with(['project', 'invitedBy'])
            ->latest()
            ->get();

        $this->totalProjectsCount = $this->ownedProjects->count() + $this->joinedProjects->count();
        
        $this->activeSprintsCount = 0;
        foreach ($this->ownedProjects as $project) {
            if ($project->activeSprint) {
                $this->activeSprintsCount++;
            }
        }
        foreach ($this->joinedProjects as $project) {
            if ($project->activeSprint) {
                $this->activeSprintsCount++;
            }
        }

        // Active project switching
        $activeProjectId = request()->query('project_id') ?: session('active_project_id');
        if ($activeProjectId) {
            $this->activeProject = \App\Models\Project::visibleTo($this->user)
                ->with(['owner', 'activeSprint', 'members'])
                ->find($activeProjectId);
        }
        
        if (!$this->activeProject) {
            $allProjects = \App\Models\Project::visibleTo($this->user)
                ->with(['owner', 'activeSprint', 'members'])
                ->latest()
                ->get();
            if (!$allProjects->isEmpty()) {
                $this->activeProject = $allProjects->first();
                session(['active_project_id' => $this->activeProject->id]);
            }
        }
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <div class="mb-8">
        <h1 class="text-5xl font-black text-[#6E5003]">Hello, {{ explode(' ', $user->name)[0] }}!</h1>
        <p class="text-sm text-[#876A1A] mt-1">Keep track of your sprints and team assignments.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Projects -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-[#6E5003]/10">
            <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Total Projects</h3>
            <p class="text-4xl font-extrabold text-[#604B10] mt-2">{{ $totalProjectsCount }}</p>
        </div>
        
        <!-- Active Sprints -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-[#6E5003]/10">
            <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Active Sprints</h3>
            <p class="text-4xl font-extrabold text-[#604B10] mt-2">{{ $activeSprintsCount }}</p>
        </div>
        
        <!-- Pending Invitations -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl border border-[#6E5003]/10">
            <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Pending Invites</h3>
            <p class="text-4xl font-extrabold text-[#604B10] mt-2">{{ $pendingInvitations->count() }}</p>
        </div>
    </div>

    <!-- Active Project Details -->
    @if ($activeProject)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Project details & Active Sprint -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Project Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50 transition-all duration-300">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-black text-[#604B10] tracking-tight">{{ $activeProject->name }}</h2>
                            <p class="text-[#6E5003] mt-3 leading-relaxed font-medium">
                                {{ $activeProject->description ?: 'No description provided.' }}
                            </p>
                        </div>
                    </div>

                    @if ($activeProject->product_goal)
                        <div class="mt-6 p-4 rounded-2xl bg-[#FDCB40]/10 border border-[#FDCB40]/25">
                            <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider flex items-center gap-1.5">
                                <x-heroicon-s-trophy class="w-4 h-4"/>
                                Product Goal
                            </h4>
                            <p class="text-sm font-semibold text-[#6E5003] mt-1.5">
                                {{ $activeProject->product_goal }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Active Sprint Section -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50">
                    <div class="flex items-center justify-between pb-4 mb-5">
                        <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2">
                            <x-heroicon-s-bolt class="w-5 h-5 text-orange-500 animate-pulse"/>
                            Active Sprint
                        </h3>
                    </div>

                    @if ($activeProject->activeSprint)
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/20">
                                <div>
                                    <h4 class="font-extrabold text-[#604B10] text-lg">{{ $activeProject->activeSprint->name }}</h4>
                                    <p class="text-xs text-[#876A1A] mt-1 flex items-center gap-1">
                                        <x-heroicon-s-calendar class="w-3.5 h-3.5"/>
                                        {{ $activeProject->activeSprint->start_date->format('M d, Y') }} — {{ $activeProject->activeSprint->end_date->format('M d, Y') }}
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-green-500/10 text-green-700 border border-green-500/20">
                                        Active
                                    </span>
                                </div>
                            </div>
                            
                            @if($activeProject->activeSprint->sprint_goal)
                                <div class="text-sm">
                                    <span class="font-extrabold text-[#876A1A] text-xs uppercase tracking-wider block">Sprint Goal</span>
                                    <p class="text-[#6E5003] font-medium mt-1 leading-relaxed">
                                        {{ $activeProject->activeSprint->sprint_goal }}
                                    </p>
                                </div>
                            @endif
                            
                            <div class="pt-4 flex gap-3">
                                <a href="{{ route('sprint-board') }}" 
                                   wire:navigate
                                   class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:shadow-md hover:bg-[#FDCB40]/90 transition-all duration-150 flex items-center gap-1.5">
                                    <x-heroicon-s-rectangle-stack class="w-4 h-4"/>
                                    Go to Sprint Board
                                </a>
                                <a href="{{ route('backlog') }}" 
                                   wire:navigate
                                   class="px-5 py-2.5 rounded-full bg-white text-[#604B10] border border-[#6E5003]/20 text-sm font-extrabold hover:bg-[#FDCB40]/10 transition-all duration-150 flex items-center gap-1.5">
                                    <x-heroicon-s-numbered-list class="w-4 h-4"/>
                                    Manage Backlog
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="py-6 text-center space-y-3">
                            <div class="w-12 h-12 rounded-full bg-[#FDCB40]/10 flex items-center justify-center mx-auto text-[#876A1A]">
                                <x-heroicon-s-calendar class="w-6 h-6"/>
                            </div>
                            <p class="text-sm font-bold text-[#876A1A]">No active sprint for this project</p>
                            <p class="text-xs text-[#876A1A]/70 max-w-sm mx-auto">Sprints keep your team aligned and focused on short-term goals.</p>
                            <div class="pt-2">
                                <a href="{{ route('sprint-board') }}" 
                                   wire:navigate
                                   class="inline-flex px-4 py-2 rounded-full bg-[#FDCB40] text-[#604B10] text-xs font-extrabold hover:bg-[#FDCB40]/90 transition-colors">
                                    Go to sprint board
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Side: Project members & Owner -->
            <div class="space-y-6">
                <!-- Team Members Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl">
                    <h3 class="text-lg font-black text-[#604B10] pb-4 mb-4 flex items-center gap-2">
                        <x-heroicon-s-users class="w-5 h-5"/>
                        Team Members
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Owner -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#FDCB40]/30 text-[#604B10] font-black flex items-center justify-center overflow-hidden">
                                @if($activeProject->owner->avatar_url)
                                    <img src="{{ $activeProject->owner->avatar_url }}" alt="{{ $activeProject->owner->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($activeProject->owner->name, 0, 2)) }}
                                @endif
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-[#604B10]">{{ $activeProject->owner->name }}</h4>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#604B10]/10 text-[#604B10]">
                                    Product Owner
                                </span>
                            </div>
                        </div>

                        <!-- Active Members -->
                        @if($activeProject->members && !$activeProject->members->isEmpty())
                            @foreach($activeProject->members as $member)
                                @if($member->id !== $activeProject->owner->id)
                                    <div class="flex items-center gap-3 pt-2">
                                        <div class="w-10 h-10 rounded-full bg-[#FDCB40]/15 text-[#604B10] font-extrabold flex items-center justify-center overflow-hidden">
                                            @if($member->avatar_url)
                                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-full h-full object-cover">
                                            @else
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-sm text-[#604B10]">{{ $member->name }}</h4>
                                            <span class="text-[10px] text-[#876A1A] font-semibold">
                                                {{ ucfirst($member->pivot->role ?? 'Member') }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <p class="text-xs text-[#876A1A]/70 italic py-2">No other active members in this project yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl border border-white/50 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-folder-open class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Project Selected</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                You don't have any projects yet or haven't selected one. Create a project to start planning backlogs and sprints!
            </p>
        </div>
    @endif
</div>