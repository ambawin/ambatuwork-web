<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $sprints;
    public $selectedSprintId;
    public $selectedSprint;
    public $groupedItems = [];
    public $isOwner = false;

    #[Url(as: 'tab')]
    public $activeTab = 'board';

    // Daily Check-ins properties
    public $showCheckinModal = false;
    public $checkinYesterday = '';
    public $checkinToday = '';
    public $checkinBlockers = '';
    public $checkinConfidence = 4;
    public $checkinsHistory = [];
    public $showHistoryModal = false;

    // Impediments/Blockers properties
    public $showImpedimentsModal = false;
    public $projectImpediments = [];
    public $newImpedimentTitle = '';
    public $newImpedimentDescription = '';
    public $showCreateImpediment = false;

    // Sprint Review properties
    public $showReviewModal = false;
    public $reviewSummary = '';
    public $reviewDemoUrl = '';
    public $reviewItems = [];

    public function mount()
    {
        $activeProjectId = request()->query('project_id') ?: session('active_project_id');
        $user = Auth::user();

        if ($activeProjectId) {
            $this->activeProject = \App\Models\Project::visibleTo($user)->find($activeProjectId);
        }

        if (!$this->activeProject) {
            $allProjects = \App\Models\Project::visibleTo($user)->latest()->get();
            if (!$allProjects->isEmpty()) {
                $this->activeProject = $allProjects->first();
                session(['active_project_id' => $this->activeProject->id]);
            }
        }

        if ($this->activeProject) {
            $this->isOwner = $this->activeProject->isOwnedBy($user) || $this->activeProject->roleFor($user) === 'owner';
        }

        $this->loadSprintDetails();

        if ($this->selectedSprint && strtolower($this->selectedSprint->status) !== 'closed' && $this->activeTab === 'ending') {
            $this->activeTab = 'board';
        }
    }

    public function loadSprintDetails()
    {
        if (!$this->activeProject) {
            $this->sprints = collect();
            $this->groupedItems = [];
            $this->selectedSprint = null;
            return;
        }

        // Fetch all sprints
        $this->sprints = $this->activeProject->sprints()
            ->withCount('items')
            ->orderBy('status', 'asc') // Active and planned first, closed later
            ->orderBy('start_date', 'desc')
            ->get();

        // If no sprint is selected, determine default (active first, fallback to first in list)
        if (!$this->selectedSprintId && !$this->sprints->isEmpty()) {
            $activeSprint = $this->sprints->firstWhere('status', 'active');
            $this->selectedSprintId = $activeSprint ? $activeSprint->id : $this->sprints->first()->id;
        }

        if ($this->selectedSprintId) {
            $this->selectedSprint = $this->activeProject->sprints()
                ->with(['createdBy', 'sprintReview'])
                ->find($this->selectedSprintId);
        } else {
            $this->selectedSprint = null;
        }

        // Load items for the selected sprint
        if ($this->selectedSprint) {
            $items = $this->selectedSprint->items()
                ->with(['createdBy', 'assignedTo'])
                ->get();

            $this->groupedItems = [
                'selected' => $items->where('status', 'selected')->values(),
                'in_progress' => $items->where('status', 'in_progress')->values(),
                'in_review' => $items->where('status', 'in_review')->values(),
                'done' => $items->where('status', 'done')->values(),
            ];
        } else {
            $this->groupedItems = [
                'selected' => collect(),
                'in_progress' => collect(),
                'in_review' => collect(),
                'done' => collect(),
            ];
        }

        // Load standup check-in history automatically
        if ($this->selectedSprint) {
            $this->checkinsHistory = $this->selectedSprint->dailyCheckins()
                ->with('user')
                ->orderBy('checkin_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $this->checkinsHistory = [];
        }

        // Load impediments automatically
        if ($this->activeProject) {
            $this->loadImpediments();
        } else {
            $this->projectImpediments = [];
        }
    }

    public function selectSprint($sprintId)
    {
        $this->selectedSprintId = $sprintId;
        $this->loadSprintDetails();

        if ($this->selectedSprint && strtolower($this->selectedSprint->status) !== 'closed' && $this->activeTab === 'ending') {
            $this->activeTab = 'board';
        }
    }

    public function moveItem($itemId, $newStatus)
    {
        if (!in_array($newStatus, ['selected', 'in_progress', 'in_review', 'done'])) {
            return;
        }

        $item = \App\Models\BacklogItem::find($itemId);
        if (!$item || $item->project_id !== $this->activeProject->id) {
            return;
        }

        if ($this->selectedSprint && strtolower($this->selectedSprint->status) === 'closed') {
            $this->dispatch('toast', message: 'You cannot move items in a closed sprint.', type: 'danger');
            return;
        }

        $user = Auth::user();

        // Enforce role-based validation
        if (!$this->isOwner) {
            if ($item->assigned_to_user_id !== $user->id) {
                $this->dispatch('toast', message: 'You can only drag/move cards that are assigned to you.', type: 'danger');
                $this->loadSprintDetails();
                return;
            }
        }

        // Execute status update
        $item->update(['status' => $newStatus]);

        // Refresh sprint items
        $this->loadSprintDetails();

        $this->dispatch('toast', message: 'Card moved to ' . ucfirst(str_replace('_', ' ', $newStatus)) . ' successfully.', type: 'success');
    }

    public function startSprint($sprintId)
    {
        $user = Auth::user();
        $sprint = \App\Models\Sprint::find($sprintId);
        
        if (!$sprint || $sprint->project_id !== $this->activeProject->id) {
            return;
        }

        // Authorization check via policies
        if ($user->cannot('start', $sprint)) {
            $this->dispatch('toast', message: 'You are not authorized to start this sprint.', type: 'danger');
            return;
        }

        if ($sprint->status !== 'planned') {
            $this->dispatch('toast', message: 'Only planned sprints can be started.', type: 'danger');
            return;
        }

        if ($this->activeProject->sprints()->where('status', 'active')->whereKeyNot($sprint->id)->exists()) {
            $this->dispatch('toast', message: 'Only one active sprint is allowed per project.', type: 'danger');
            return;
        }

        if (!$sprint->items()->exists()) {
            $this->dispatch('toast', message: 'A sprint must have at least one backlog item before it can start.', type: 'danger');
            return;
        }

        $sprint->update([
            'status' => 'active',
        ]);

        $this->dispatch('toast', message: 'Sprint started successfully.', type: 'success');
        $this->loadSprintDetails();
    }

    public function closeSprint($sprintId)
    {
        $user = Auth::user();
        $sprint = \App\Models\Sprint::find($sprintId);
        
        if (!$sprint || $sprint->project_id !== $this->activeProject->id) {
            return;
        }

        // Authorization check via policies
        if ($user->cannot('close', $sprint)) {
            $this->dispatch('toast', message: 'You are not authorized to close this sprint.', type: 'danger');
            return;
        }

        if ($sprint->status !== 'active') {
            $this->dispatch('toast', message: 'Only active sprints can be closed.', type: 'danger');
            return;
        }

        $unfinishedItemIds = $sprint->items()
            ->where('status', '!=', 'done')
            ->pluck('backlog_items.id');

        $sprint->items()
            ->whereIn('backlog_items.id', $unfinishedItemIds)
            ->update(['status' => 'ready']);

        $sprint->update([
            'status' => 'closed',
            'closed_by_user_id' => $user->id,
            'closed_at' => now(),
        ]);

        $this->dispatch('toast', message: 'Sprint closed successfully.', type: 'success');
        $this->loadSprintDetails();
    }

    // Daily Check-ins Methods
    public function openCheckin()
    {
        if (!$this->selectedSprint) return;
        $this->checkinYesterday = '';
        $this->checkinToday = '';
        $this->checkinBlockers = '';
        $this->checkinConfidence = 4;
        $this->showCheckinModal = true;
    }

    public function submitCheckin()
    {
        if (!$this->selectedSprint) return;
        $user = Auth::user();

        if ($this->selectedSprint->status !== 'active') {
            $this->dispatch('toast', message: 'You can only submit check-ins for active sprints.', type: 'danger');
            return;
        }

        $this->validate([
            'checkinYesterday' => 'nullable|string|max:1000',
            'checkinToday' => 'nullable|string|max:1000',
            'checkinBlockers' => 'nullable|string|max:1000',
            'checkinConfidence' => 'required|integer|min:1|max:5',
        ]);

        DB::transaction(function() use ($user) {
            $checkin = $this->selectedSprint->dailyCheckins()->create([
                'project_id' => $this->activeProject->id,
                'user_id' => $user->id,
                'yesterday' => $this->checkinYesterday ?: null,
                'today' => $this->checkinToday ?: null,
                'blockers' => $this->checkinBlockers ?: null,
                'confidence_score' => (int) $this->checkinConfidence,
                'checkin_date' => now()->toDateString(),
            ]);

            if ($this->checkinBlockers && trim($this->checkinBlockers) !== '') {
                $this->activeProject->impediments()->create([
                    'sprint_id' => $this->selectedSprint->id,
                    'reported_by_user_id' => $user->id,
                    'title' => 'Blocker reported by ' . $user->name . ' on ' . now()->toDateString(),
                    'description' => $this->checkinBlockers,
                    'status' => 'open',
                ]);
            }
        });

        $this->checkinYesterday = '';
        $this->checkinToday = '';
        $this->checkinBlockers = '';
        $this->checkinConfidence = 4;
        $this->dispatch('toast', message: 'Daily check-in submitted successfully.', type: 'success');
        $this->loadSprintDetails();
    }

    public function openHistory()
    {
        if (!$this->selectedSprint) return;
        $this->checkinsHistory = $this->selectedSprint->dailyCheckins()
            ->with('user')
            ->orderBy('checkin_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        $this->showHistoryModal = true;
    }

    // Impediments Methods
    public function openImpediments()
    {
        if (!$this->activeProject) return;
        $this->loadImpediments();
        $this->newImpedimentTitle = '';
        $this->newImpedimentDescription = '';
        $this->showCreateImpediment = false;
        $this->showImpedimentsModal = true;
    }

    public function loadImpediments()
    {
        $this->projectImpediments = $this->activeProject->impediments()
            ->with(['reporter', 'owner'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function saveImpediment()
    {
        if (!$this->activeProject) return;

        $this->validate([
            'newImpedimentTitle' => 'required|string|max:255',
            'newImpedimentDescription' => 'nullable|string|max:2000',
        ]);

        $activeSprint = $this->activeProject->activeSprint;

        $this->activeProject->impediments()->create([
            'sprint_id' => $activeSprint?->id,
            'reported_by_user_id' => Auth::id(),
            'title' => $this->newImpedimentTitle,
            'description' => $this->newImpedimentDescription ?: null,
            'status' => 'open',
        ]);

        $this->newImpedimentTitle = '';
        $this->newImpedimentDescription = '';
        $this->showCreateImpediment = false;
        $this->loadImpediments();
        $this->dispatch('toast', message: 'Blocker reported successfully.', type: 'success');
    }

    public function resolveImpediment($id)
    {
        $impediment = \App\Models\Impediment::where('id', $id)->first();
        if ($impediment && $impediment->project_id === $this->activeProject->id) {
            $impediment->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);
            $this->loadImpediments();
            $this->dispatch('toast', message: 'Blocker resolved successfully.', type: 'success');
        }
    }

    // Sprint Review closing wizard
    public function startCloseSprint($sprintId)
    {
        $user = Auth::user();
        $sprint = \App\Models\Sprint::find($sprintId);
        
        if (!$sprint || $sprint->project_id !== $this->activeProject->id) {
            return;
        }

        if ($user->cannot('close', $sprint)) {
            $this->dispatch('toast', message: 'You are not authorized to close this sprint.', type: 'danger');
            return;
        }

        $this->selectedSprintId = $sprintId;
        $this->selectedSprint = $sprint;
        
        // Load items for review
        $items = $sprint->items()->get();
        
        $this->reviewItems = [];
        foreach ($items as $item) {
            $this->reviewItems[] = [
                'id' => $item->id,
                'title' => $item->title,
                'points' => $item->estimate_points ?: 0,
                'decision' => $item->status === 'done' ? 'accepted' : 'carry_over',
                'notes' => '',
            ];
        }

        $this->reviewSummary = '';
        $this->reviewDemoUrl = '';
        $this->showReviewModal = true;
    }

    public function submitSprintReview()
    {
        $user = Auth::user();
        $sprint = $this->selectedSprint;

        if (!$sprint) return;

        if ($user->cannot('close', $sprint)) {
            $this->dispatch('toast', message: 'You are not authorized to close this sprint.', type: 'danger');
            return;
        }

        $this->validate([
            'reviewSummary' => 'required|string|max:5000',
            'reviewDemoUrl' => 'nullable|url|max:255',
            'reviewItems.*.decision' => 'required|string|in:accepted,carry_over,rejected',
            'reviewItems.*.notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function() use ($user, $sprint) {
            $review = \App\Models\SprintReview::updateOrCreate(
                ['sprint_id' => $sprint->id],
                [
                    'project_id' => $this->activeProject->id,
                    'summary' => $this->reviewSummary,
                    'demo_url' => $this->reviewDemoUrl ?: null,
                    'created_by_user_id' => $user->id,
                ]
            );

            $review->items()->delete();

            foreach ($this->reviewItems as $itemData) {
                $itemId = $itemData['id'];
                $decision = $itemData['decision'];

                $review->items()->create([
                    'backlog_item_id' => $itemId,
                    'decision' => $decision,
                    'notes' => $itemData['notes'] ?: null,
                    'decided_by_user_id' => $user->id,
                ]);

                $backlogItem = \App\Models\BacklogItem::find($itemId);
                if ($backlogItem) {
                    if ($decision === 'accepted') {
                        $backlogItem->update([
                            'status' => 'done',
                            'done_at' => now(),
                        ]);
                    } else {
                        $backlogItem->update([
                            'status' => 'ready',
                            'done_at' => null,
                        ]);
                    }
                }
            }

            $sprint->update([
                'status' => 'closed',
                'closed_by_user_id' => $user->id,
                'closed_at' => now(),
            ]);
        });

        $this->showReviewModal = false;
        $this->dispatch('toast', message: 'Sprint closed and review saved successfully.', type: 'success');
        $this->loadSprintDetails();
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col gap-1">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Sprint Board</h1>
            @if ($activeProject && $isOwner)
                <a href="{{ route('sprints.create') }}?project_id={{ $activeProject->id }}" 
                   wire:navigate
                   class="px-4 py-2.5 rounded-full bg-[#604B10] text-[#FDCB40] text-xs font-extrabold flex items-center gap-1 hover:bg-[#604B10]/95 transition no-underline shadow-sm shrink-0">
                    <x-heroicon-s-plus class="w-3.5 h-3.5"/>
                    Add Sprint
                </a>
            @endif
        </div>
        <p class="text-sm text-[#876A1A]">
            @if ($activeProject)
                Manage sprint tasks and columns for <span class="font-extrabold text-[#604B10]">{{ $activeProject->name }}</span>.
            @else
                Select a project to view and manage active sprint boards.
            @endif
        </p>
    </div>

    <!-- Selected Sprint Overview -->
    @if ($selectedSprint)
        <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <!-- Left Side: Sprint Detail -->
            <div class="flex-grow text-left">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-black text-[#604B10]">{{ $selectedSprint->name }}</h2>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider 
                        @if(strtolower($selectedSprint->status) === 'active') bg-[#FDCB40] text-[#604B10]
                        @elseif(strtolower($selectedSprint->status) === 'planned') bg-[#604B10]/10 text-[#604B10]
                        @else bg-[#604B10] text-white @endif">
                        {{ $selectedSprint->status }}
                    </span>
                    <span class="text-xs font-bold text-[#876A1A] flex items-center gap-1 bg-[#FDCB40]/10 px-2.5 py-1 rounded-full shrink-0">
                        <x-heroicon-s-calendar class="w-3.5 h-3.5 text-[#876A1A]"/>
                        {{ $selectedSprint->start_date->format('M d, Y') }} — {{ $selectedSprint->end_date->format('M d, Y') }}
                    </span>
                </div>
                @if ($selectedSprint->sprint_goal)
                    <p class="text-xs text-[#876A1A] font-bold mt-1.5 flex items-center gap-1">
                        <x-heroicon-s-trophy class="w-4 h-4"/>
                        Goal: <span class="text-[#6E5003] font-medium italic">{{ $selectedSprint->sprint_goal }}</span>
                    </p>
                @endif
            </div>

            <!-- Right Side: Actions (Add, Change, Start/Close) -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                @if (strtolower($selectedSprint->status) !== 'closed' && $isOwner)
                    @if (strtolower($selectedSprint->status) === 'planned')
                        <button type="button"
                                wire:click="startSprint({{ $selectedSprint->id }})"
                                class="px-4 py-2.5 rounded-full bg-[#FDCB40] hover:bg-[#FDCB40]/90 text-[#604B10] text-xs font-extrabold uppercase tracking-wider transition-colors cursor-pointer border-none outline-none flex items-center gap-1.5 shadow-sm">
                            <x-heroicon-s-play class="w-3.5 h-3.5"/>
                            Start Sprint
                        </button>
                    @elseif (strtolower($selectedSprint->status) === 'active')
                        <button type="button"
                                wire:click="startCloseSprint({{ $selectedSprint->id }})"
                                class="px-4 py-2.5 rounded-full bg-[#604B10] hover:bg-[#604B10]/90 text-white text-xs font-extrabold uppercase tracking-wider transition-colors cursor-pointer border-none outline-none flex items-center gap-1.5 shadow-sm">
                            <x-heroicon-s-x-circle class="w-3.5 h-3.5"/>
                            Close Sprint
                        </button>
                    @endif
                @endif


                @if (!$sprints->isEmpty())
                    <!-- Custom Sprint Selection Dropdown using Alpine.js -->
                    <div x-data="{ open: false }" 
                         x-on:click.outside="open = false"
                         class="relative shrink-0 select-none">
                        
                        <!-- Toggle Button -->
                        <button x-on:click="open = !open" 
                                class="bg-[#FDCB40] text-[#604B10] px-4 py-2.5 rounded-full text-xs font-black outline-none cursor-pointer border-none pr-8 flex items-center hover:bg-[#FDCB40]/90 transition-colors shadow-sm relative">
                             Change Sprint
                             
                             <!-- Custom Chevron -->
                             <div class="absolute inset-y-0 right-0 flex items-center px-2 text-[#604B10] transition-transform duration-200"
                                  :class="{ 'rotate-180': open }">
                                 <svg class="fill-currentColor h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                     <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                 </svg>
                             </div>
                         </button>

                         <!-- Dropdown Options List -->
                         <ul x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute top-[calc(100%+8px)] right-0 min-w-[240px] bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_20px_50px_rgba(96,75,16,0.15)] m-0 p-1.5 list-none z-[100] flex flex-col gap-1"
                             style="display: none;">
                             @foreach ($sprints as $sprint)
                                 <li>
                                     <button x-on:click="open = false; $wire.selectSprint('{{ $sprint->id }}')"
                                             class="w-full text-left px-4 py-2 text-xs rounded-xl font-bold transition-all duration-150 border-none outline-none cursor-pointer flex items-center justify-between
                                                 {{ $selectedSprintId == $sprint->id ? 'bg-[#FDCB40] text-[#604B10]' : 'text-[#876A1A] hover:bg-[#FDCB40]/20 hover:text-[#604B10]' }}">
                                         <span>{{ $sprint->name }}</span>
                                         <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full
                                             @if(strtolower($sprint->status) === 'active') bg-[#FDCB40] text-[#604B10]
                                             @elseif(strtolower($sprint->status) === 'planned') bg-[#604B10]/10 text-[#604B10]
                                             @else bg-[#604B10] text-white @endif">
                                             {{ $sprint->status }}
                                         </span>
                                     </button>
                                 </li>
                             @endforeach
                         </ul>
                     </div>
                @endif
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex flex-wrap gap-2 mb-8 bg-white/40 backdrop-blur-md p-1.5 rounded-full w-fit">
            <!-- Board Tab -->
            <button type="button"
                    wire:click="$set('activeTab', 'board')" 
                    class="px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider transition-all duration-200 cursor-pointer border-none outline-none 
                    {{ $activeTab === 'board' ? 'bg-[#604B10] text-[#FDCB40] shadow-sm' : 'bg-transparent text-[#604B10] hover:bg-[#FDCB40]/25' }}">
                Board
            </button>

            <!-- Daily Standup Tab -->
            <button type="button"
                    wire:click="$set('activeTab', 'standup')" 
                    class="px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider transition-all duration-200 cursor-pointer border-none outline-none 
                    {{ $activeTab === 'standup' ? 'bg-[#604B10] text-[#FDCB40] shadow-sm' : 'bg-transparent text-[#604B10] hover:bg-[#FDCB40]/25' }}">
                Daily Standup
            </button>

            <!-- Blockers Tab -->
            <button type="button"
                    wire:click="$set('activeTab', 'blockers')" 
                    class="px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider transition-all duration-200 cursor-pointer border-none outline-none 
                    {{ $activeTab === 'blockers' ? 'bg-[#604B10] text-[#FDCB40] shadow-sm' : 'bg-transparent text-[#604B10] hover:bg-[#FDCB40]/25' }}">
                Blockers
            </button>

            <!-- Ending Tab -->
            @php
                $isClosed = $selectedSprint && strtolower($selectedSprint->status) === 'closed';
            @endphp
            <button type="button"
                    @if ($isClosed)
                        wire:click="$set('activeTab', 'ending')"
                    @endif
                    class="px-6 py-2.5 rounded-full text-xs font-black uppercase tracking-wider transition-all duration-200 border-none outline-none flex items-center gap-1.5
                    {{ !$isClosed ? 'bg-[#604B10]/5 text-[#604B10]/40 cursor-not-allowed' : ($activeTab === 'ending' ? 'bg-[#604B10] text-[#FDCB40] shadow-sm cursor-pointer' : 'bg-transparent text-[#604B10] hover:bg-[#FDCB40]/25 cursor-pointer') }}"
                    title="{{ !$isClosed ? 'Available only once the sprint has ended' : 'Sprint retrospect & peer review' }}">
                    @if (!$isClosed)
                        <x-heroicon-s-lock-closed class="w-3.5 h-3.5 text-[#604B10]/40"/>
                    @else
                        <x-heroicon-s-lock-open class="w-3.5 h-3.5 {{ $activeTab === 'ending' ? 'text-[#FDCB40]' : 'text-[#604B10]' }}"/>
                    @endif
                    Ending
            </button>
        </div>

        @if ($activeTab === 'board')
            @php
                $isClosed = $selectedSprint && strtolower($selectedSprint->status) === 'closed';
            @endphp

            @if ($isClosed)
                <div class="mb-6 p-4 bg-[#604B10]/5 text-[#604B10] rounded-2xl flex items-center gap-3 text-sm font-semibold">
                    <x-heroicon-s-lock-closed class="w-5 h-5 text-[#604B10] shrink-0"/>
                    <span>This sprint has ended. The board is now read-only and card movement is disabled.</span>
                </div>
            @endif

            <!-- Kanban Board Layout -->
            @php
                $columns = [
                    'selected' => ['title' => 'Backlog'],
                    'in_progress' => ['title' => 'In Progress'],
                    'in_review' => ['title' => 'In Review'],
                    'done' => ['title' => 'Done'],
                ];
            @endphp

            <div x-data="{
                    dragItemId: null,
                    dragOverColumn: null,
                    startDrag(e, itemId) {
                        this.dragItemId = itemId;
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', itemId);
                    },
                    dropOnColumn(e, columnKey) {
                        const itemId = e.dataTransfer.getData('text/plain') || this.dragItemId;
                        if (itemId) {
                            $wire.moveItem(itemId, columnKey);
                        }
                        this.dragItemId = null;
                        this.dragOverColumn = null;
                    },
                    endDrag() {
                        setTimeout(() => {
                            this.dragItemId = null;
                            this.dragOverColumn = null;
                        }, 50);
                    }
                }"
                x-on:dragend.window="endDrag()"
                class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">

                @foreach ($columns as $columnKey => $columnDef)
                    @php
                        $columnItems = $groupedItems[$columnKey] ?? collect();
                    @endphp

                <!-- Column Drop Zone -->
                <div class="flex flex-col bg-white/45 backdrop-blur-md p-4 rounded-3xl min-h-[550px] transition-all duration-200"
                     x-on:dragover.prevent="dragOverColumn = '{{ $columnKey }}'"
                     x-on:dragleave.self="dragOverColumn = (dragOverColumn === '{{ $columnKey }}') ? null : dragOverColumn"
                     x-on:drop.prevent="dropOnColumn($event, '{{ $columnKey }}')"
                     x-bind:class="dragOverColumn === '{{ $columnKey }}' ? 'bg-[#FDCB40]/15 scale-[1.01] shadow-sm' : 'bg-white/45'">
                    
                    <!-- Column Header -->
                    <div class="flex items-center justify-between mb-4 pb-2 pt-1">
                        <span class="font-black text-sm text-[#604B10] tracking-wider">{{ $columnDef['title'] }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#6E5003]/15 text-[#604B10]">
                            {{ $columnItems->count() }}
                        </span>
                    </div>

                    <!-- Items List -->
                    <div class="flex-grow flex flex-col gap-4">
                        @if (!$columnItems->isEmpty())
                            @foreach ($columnItems as $item)
                                @php
                                    $canMove = !$isClosed && ($isOwner || ($item->assigned_to_user_id === Auth::id()));
                                @endphp

                                <!-- Card item -->
                                <div draggable="{{ $canMove ? 'true' : 'false' }}"
                                     @if($canMove)
                                     x-on:dragstart="startDrag($event, '{{ $item->id }}')"
                                     @endif
                                     class="bg-white p-4.5 rounded-2xl transition-all duration-150 select-none text-left shadow-[0_2px_8px_rgba(96,75,16,0.04)]
                                         {{ $canMove ? 'cursor-grab active:cursor-grabbing hover:-translate-y-0.5 hover:shadow-[0_8px_20px_rgba(96,75,16,0.08)]' : 'opacity-70 cursor-not-allowed' }}">
                                     
                                     <div class="flex items-start justify-between gap-2 mb-2">
                                         <!-- Type Tag -->
                                         @if (strtolower($item->type) === 'bug')
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-[#604B10] text-white">
                                                 Bug
                                             </span>
                                         @elseif (strtolower($item->type) === 'chore')
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-[#604B10]/10 text-[#604B10]">
                                                 Chore
                                             </span>
                                         @else
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-[#FDCB40] text-[#604B10]">
                                                 Story
                                             </span>
                                         @endif

                                         <!-- Lock indicator if non-movable -->
                                         @if (!$canMove)
                                             <span class="text-[#604B10] shrink-0" title="{{ $isClosed ? 'Sprint has ended' : 'Locked: Assigned to another user' }}">
                                                 <x-heroicon-s-lock-closed class="w-3.5 h-3.5"/>
                                             </span>
                                         @endif
                                     </div>

                                     <!-- Title -->
                                     <h4 class="font-extrabold text-sm text-[#604B10] leading-snug line-clamp-3 mb-4">
                                         {{ $item->title }}
                                     </h4>

                                     <!-- Footer -->
                                     <div class="flex items-center justify-between pt-3">
                                         <!-- Points -->
                                         <span class="text-[9px] font-black text-white bg-[#604B10] px-2.5 py-1 rounded-full">
                                             {{ $item->estimate_points ?: '0' }} pts
                                         </span>

                                         <!-- Assignee Circle -->
                                         <div class="w-7 h-7 rounded-full bg-[#FDCB40]/20 text-[#604B10] font-black text-[9px] flex items-center justify-center overflow-hidden" 
                                              title="{{ $item->assignedTo ? 'Assigned to: ' . $item->assignedTo->name : 'Unassigned' }}">
                                             @if ($item->assignedTo)
                                                 @if ($item->assignedTo->avatar_url)
                                                     <img src="{{ $item->assignedTo->avatar_url }}" alt="{{ $item->assignedTo->name }}" class="w-full h-full object-cover">
                                                 @else
                                                     {{ strtoupper(substr($item->assignedTo->name, 0, 2)) }}
                                                 @endif
                                             @else
                                                 <x-heroicon-s-user class="w-3.5 h-3.5 text-[#876A1A]"/>
                                             @endif
                                         </div>
                                     </div>
                                 </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        @if ($activeTab === 'standup')
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left Column: Add Daily Check-in -->
                <div class="lg:col-span-5 bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-6 text-left">
                    <h3 class="text-xl font-black text-[#604B10] flex items-center gap-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-[#604B10]"/>
                        New Standup Check-in
                    </h3>

                    @if ($selectedSprint && strtolower($selectedSprint->status) === 'active')
                        <form wire:submit.prevent="submitCheckin" class="space-y-5">
                            <!-- Yesterday -->
                            <div>
                                <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What did you complete yesterday?</label>
                                <textarea wire:model="checkinYesterday" rows="3" placeholder="e.g. Worked on invitation accept flow" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                                @error('checkinYesterday') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Today -->
                            <div>
                                <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What will you work on today?</label>
                                <textarea wire:model="checkinToday" rows="3" placeholder="e.g. Connecting dashboard project settings UI" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                                @error('checkinToday') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Blockers -->
                            <div>
                                <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What is blocking you? (Optional)</label>
                                <textarea wire:model="checkinBlockers" rows="2" placeholder="Describe any roadblocks. This will automatically report an impediment." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                                @error('checkinBlockers') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Confidence score -->
                            <div>
                                <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Confidence score (1 - Low, 5 - High)</label>
                                <input type="range" wire:model="checkinConfidence" min="1" max="5" class="w-full accent-[#604B10]" />
                                <div class="flex justify-between text-xs text-[#876A1A] font-bold mt-1 px-1">
                                    <span>1</span>
                                    <span>2</span>
                                    <span>3</span>
                                    <span>4</span>
                                    <span>5</span>
                                </div>
                                @error('checkinConfidence') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="w-full px-5 py-3 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                                Submit Check-in
                            </button>
                        </form>
                    @else
                        <div class="p-6 bg-[#604B10]/5 rounded-2xl text-center space-y-3">
                            <div class="w-12 h-12 rounded-full bg-[#604B10]/10 flex items-center justify-center mx-auto text-[#876A1A]">
                                <x-heroicon-s-lock-closed class="w-6 h-6"/>
                            </div>
                            <h4 class="font-extrabold text-sm text-[#604B10]">Check-ins Locked</h4>
                            <p class="text-xs text-[#876A1A] leading-relaxed">
                                Daily check-ins are only available when the selected sprint is actively running.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Standup History -->
                <div class="lg:col-span-7 bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-6 text-left">
                    <h3 class="text-xl font-black text-[#604B10] flex items-center gap-2">
                        <x-heroicon-s-clipboard-document-list class="w-5 h-5 text-[#604B10]"/>
                        Standup Check-in History
                    </h3>

                    @if (count($checkinsHistory) === 0)
                        <div class="p-12 text-center text-[#876A1A] font-semibold italic">
                            No standup check-ins have been submitted for this sprint yet.
                        </div>
                    @else
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                            @foreach ($checkinsHistory as $chk)
                                <div class="bg-[#FDCB40]/5 p-5 rounded-2xl hover:bg-[#FDCB40]/10 transition duration-150">
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-[#FDCB40]/30 text-[#604B10] font-black text-xs flex items-center justify-center overflow-hidden">
                                                @if ($chk->user->avatar_url)
                                                    <img src="{{ $chk->user->avatar_url }}" alt="{{ $chk->user->name }}" class="w-full h-full object-cover">
                                                @else
                                                    {{ strtoupper(substr($chk->user->name, 0, 2)) }}
                                                @endif
                                            </div>
                                            <span class="font-extrabold text-sm text-[#604B10]">{{ $chk->user->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-semibold text-[#876A1A]">
                                            <span>{{ $chk->checkin_date->format('M d, Y') }}</span>
                                            <span class="px-2.5 py-0.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-[10px] font-black uppercase tracking-wider">Conf: {{ $chk->confidence_score }}/5</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2 text-sm text-[#6E5003]">
                                        @if ($chk->yesterday)
                                            <p><strong class="text-[#604B10]">Yesterday:</strong> {{ $chk->yesterday }}</p>
                                        @endif
                                        @if ($chk->today)
                                            <p><strong class="text-[#604B10]">Today:</strong> {{ $chk->today }}</p>
                                        @endif
                                        @if ($chk->blockers)
                                            <p class="p-3 bg-[#604B10]/5 text-[#604B10] rounded-2xl mt-2"><strong class="font-extrabold">Roadblock:</strong> {{ $chk->blockers }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($activeTab === 'blockers')
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left Column: Report Blocker -->
                <div class="lg:col-span-5 bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-6 text-left">
                    <h3 class="text-xl font-black text-[#604B10] flex items-center gap-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-[#604B10]"/>
                        Report a Blocker
                    </h3>

                    @if ($selectedSprint && strtolower($selectedSprint->status) !== 'closed')
                        <form wire:submit.prevent="saveImpediment" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Blocker Title</label>
                                <input type="text" wire:model="newImpedimentTitle" placeholder="e.g. Slow local webpack build" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                                @error('newImpedimentTitle') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Blocker Description</label>
                                <textarea wire:model="newImpedimentDescription" rows="4" placeholder="Describe the blockers in detail so the Scrum master can help resolve it..." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                                @error('newImpedimentDescription') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="w-full px-5 py-3 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                                Report Blocker
                            </button>
                        </form>
                    @else
                        <div class="p-6 bg-[#604B10]/5 rounded-2xl text-center space-y-3">
                            <div class="w-12 h-12 rounded-full bg-[#604B10]/10 flex items-center justify-center mx-auto text-[#876A1A]">
                                <x-heroicon-s-lock-closed class="w-6 h-6"/>
                            </div>
                            <h4 class="font-extrabold text-sm text-[#604B10]">Reporting Blockers Locked</h4>
                            <p class="text-xs text-[#876A1A] leading-relaxed">
                                You cannot report new blockers because this sprint has ended.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Blockers List -->
                <div class="lg:col-span-7 bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-6 text-left">
                    <h3 class="text-xl font-black text-[#604B10] flex items-center gap-2">
                        <x-heroicon-s-fire class="w-5 h-5 text-[#604B10]"/>
                        Active & Resolved Blockers
                    </h3>

                    @if (count($projectImpediments) === 0)
                        <div class="p-12 text-center text-[#876A1A] font-semibold italic">
                            No blockers have been reported for this project.
                        </div>
                    @else
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                            @foreach ($projectImpediments as $imp)
                                <div class="p-4.5 rounded-2xl transition-all duration-300 {{ $imp->status === 'resolved' ? 'bg-[#604B10]/5 opacity-60' : 'bg-[#FDCB40]/10' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-grow">
                                            <h4 class="font-extrabold text-base leading-snug {{ $imp->status === 'resolved' ? 'text-[#876A1A] line-through' : 'text-[#604B10]' }}">{{ $imp->title }}</h4>
                                            @if ($imp->description)
                                                <p class="text-xs mt-1.5 {{ $imp->status === 'resolved' ? 'text-[#876A1A]/80' : 'text-[#876A1A]' }}">{{ $imp->description }}</p>
                                            @endif
                                            <p class="text-[10px] text-[#876A1A] font-semibold mt-2.5">
                                                Reported by {{ $imp->reporter->name }} {{ $imp->created_at->diffForHumans() }}
                                                @if ($imp->status === 'resolved')
                                                    | Resolved {{ $imp->resolved_at ? $imp->resolved_at->diffForHumans() : '' }}
                                                @endif
                                            </p>
                                        </div>

                                        @if ($imp->status === 'open')
                                            <button wire:click="resolveImpediment({{ $imp->id }})" class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase bg-[#604B10] hover:bg-[#604B10]/90 text-[#FDCB40] transition border-none cursor-pointer outline-none shrink-0 shadow-sm">
                                                Resolve
                                            </button>
                                        @else
                                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#604B10]/10 text-[#604B10] shrink-0">
                                                Resolved
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($activeTab === 'ending' && strtolower($selectedSprint->status) === 'closed')
            <div class="space-y-6 text-left">
                <!-- Retro and Peer Review Quick Links -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Retrospective Card -->
                    <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-4 hover:shadow-md transition">
                        <div class="w-10 h-10 rounded-full bg-[#604B10]/10 flex items-center justify-center text-[#604B10]">
                            <x-heroicon-s-chat-bubble-left-right class="w-5 h-5"/>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-lg text-[#604B10]">Sprint Retrospective</h4>
                            <p class="text-xs text-[#876A1A] mt-1">Reflect on what went well, what could be improved, and compile action items for the next sprint.</p>
                        </div>
                        <a href="{{ route('retrospective', ['project' => $activeProject->id, 'sprint' => $selectedSprint->id]) }}" 
                           wire:navigate 
                           class="inline-flex px-5 py-2.5 rounded-full bg-[#604B10] text-[#FDCB40] text-xs font-black uppercase tracking-wider no-underline transition hover:bg-[#604B10]/90">
                            Start Retrospective
                        </a>
                    </div>

                    <!-- Peer Review Card -->
                    <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-4 hover:shadow-md transition">
                        <div class="w-10 h-10 rounded-full bg-[#604B10]/10 flex items-center justify-center text-[#604B10]">
                            <x-heroicon-s-user-group class="w-5 h-5"/>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-lg text-[#604B10]">Peer Review Cycle</h4>
                            <p class="text-xs text-[#876A1A] mt-1">Provide feedback and evaluate team member performance across collaboration, delivery, and communication.</p>
                        </div>
                        <a href="{{ route('peer-review', ['project' => $activeProject->id, 'sprint' => $selectedSprint->id]) }}" 
                           wire:navigate 
                           class="inline-flex px-5 py-2.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-xs font-black uppercase tracking-wider no-underline hover:bg-[#FDCB40]/25 transition">
                            Open Peer Reviews
                        </a>
                    </div>
                </div>

                <!-- Sprint Review Summary -->
                @if ($selectedSprint->sprintReview)
                    <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] space-y-4">
                        <h4 class="font-extrabold text-[#604B10] text-sm uppercase tracking-wider flex items-center gap-2">
                            <x-heroicon-s-clipboard-document class="w-4 h-4 text-[#876A1A]"/>
                            Sprint Review Summary
                        </h4>
                        <p class="text-[#6E5003] text-sm leading-relaxed font-semibold">{{ $selectedSprint->sprintReview->summary }}</p>
                        
                        @if ($selectedSprint->sprintReview->demo_url)
                            <div class="pt-2">
                                <a href="{{ $selectedSprint->sprintReview->demo_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-[#604B10] hover:text-[#6E5003] font-black text-xs hover:underline">
                                    <x-heroicon-s-link class="w-4 h-4"/>
                                    View Demo URL
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    @else
        <!-- Empty Sprints state -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl shadow-[0_8px_30px_rgba(96,75,16,0.04)] text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-calendar class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Sprints Found</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                There are no sprints defined for this project. Sprints organize backlog items into dedicated timelines. Create a sprint to activate the board!
            </p>
        </div>
    @endif



    <!-- Sprint Review Modal -->
    @if ($showReviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-2xl w-full shadow-[0_25px_60px_-15px_rgba(96,75,16,0.25)] max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showReviewModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Sprint Review & Closing</h3>

                <form wire:submit.prevent="submitSprintReview" class="space-y-6 text-left">
                    <!-- General summary -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Sprint review summary</label>
                        <textarea wire:model="reviewSummary" rows="3" placeholder="Describe the outcomes of this sprint, demo session feedback, etc." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('reviewSummary') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Demo URL -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Demo URL (Optional)</label>
                        <input type="url" wire:model="reviewDemoUrl" placeholder="https://demo.example.com" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl border-none outline-none focus:bg-[#FDCB40]/20 font-semibold" />
                        @error('reviewDemoUrl') <span class="text-xs text-[#604B10] font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Review items decision table -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-3">Item Acceptance Decisions</label>
                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            @foreach ($reviewItems as $index => $itemData)
                                <div class="p-4 bg-[#604B10]/5 rounded-2xl">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                                        <span class="font-extrabold text-sm text-[#604B10]">{{ $itemData['title'] }} ({{ $itemData['points'] }} pts)</span>
                                        <select wire:model="reviewItems.{{ $index }}.decision" class="bg-white text-xs font-bold border border-[#604B10]/20 text-[#604B10] px-3 py-1.5 rounded-lg cursor-pointer outline-none">
                                            <option value="accepted">Accepted (Done)</option>
                                            <option value="carry_over">Carry Over (Backlog)</option>
                                            <option value="rejected">Rejected (Backlog)</option>
                                        </select>
                                    </div>
                                    <input type="text" wire:model="reviewItems.{{ $index }}.notes" placeholder="Notes (e.g. Minor tweak required in styling)" class="w-full bg-white text-xs font-semibold px-3 py-2 border border-[#604B10]/10 text-[#604B10] focus:border-[#FDCB40]/55 focus:bg-[#FDCB40]/10 rounded-lg outline-none" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-6">
                        <button type="button" wire:click="$set('showReviewModal', false)" class="px-5 py-2.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-sm font-extrabold hover:bg-[#604B10]/20 transition cursor-pointer outline-none border-none">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#604B10] hover:bg-[#604B10]/90 text-white text-sm font-extrabold hover:shadow-md transition cursor-pointer border-none outline-none">
                            Submit Review & Close Sprint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
