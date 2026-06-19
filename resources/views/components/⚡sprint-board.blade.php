<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
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
    }

    public function selectSprint($sprintId)
    {
        $this->selectedSprintId = $sprintId;
        $this->loadSprintDetails();
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

        $this->showCheckinModal = false;
        $this->dispatch('toast', message: 'Daily check-in submitted successfully.', type: 'success');
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
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Sprint Board</h1>
            <p class="text-sm text-[#876A1A] mt-1">
                @if ($activeProject)
                    Manage sprint tasks and columns for <span class="font-extrabold text-[#604B10]">{{ $activeProject->name }}</span>.
                @else
                    Select a project to view and manage active sprint boards.
                @endif
            </p>
        </div>

        @if ($activeProject)
            <div class="flex items-center gap-3">
                <!-- Blocker management -->
                <button type="button"
                        wire:click="openImpediments"
                        class="px-5 py-2.5 rounded-full bg-white text-[#604B10] text-sm font-extrabold flex items-center gap-1.5 cursor-pointer shrink-0 border-none outline-none">
                    <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-amber-600"/>
                    Blockers
                    @php
                        $openBlockersCount = $activeProject->impediments()->where('status', 'open')->count();
                    @endphp
                    @if ($openBlockersCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white leading-none">
                            {{ $openBlockersCount }}
                        </span>
                    @endif
                </button>

                @if ($selectedSprint)
                    <button type="button"
                            wire:click="openHistory"
                            class="px-5 py-2.5 rounded-full bg-white text-[#604B10] text-sm font-extrabold flex items-center gap-1.5 cursor-pointer shrink-0 border-none outline-none">
                        <x-heroicon-s-clipboard-document-list class="w-4 h-4"/>
                        Standup History
                    </button>

                    @if ($selectedSprint->status === 'active')
                        <button type="button"
                                wire:click="openCheckin"
                                class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold flex items-center gap-1.5 cursor-pointer shrink-0 border-none outline-none">
                            <x-heroicon-s-check-circle class="w-4 h-4"/>
                            Daily Check-in
                        </button>
                    @endif
                @endif

                @if ($isOwner)
                    <a href="{{ route('sprints.create') }}?project_id={{ $activeProject->id }}" 
                       wire:navigate
                       class="px-5 py-2.5 rounded-full bg-white text-[#604B10] text-sm font-extrabold flex items-center gap-1.5 cursor-pointer no-underline shrink-0">
                        <x-heroicon-s-plus class="w-4 h-4"/>
                        Add Sprint
                    </a>
                @endif

                @if (!$sprints->isEmpty())
                    <!-- Custom Sprint Selection Dropdown using Alpine.js -->
                    <div x-data="{ open: false }" 
                         x-on:click.outside="open = false"
                         class="flex items-center gap-3 bg-white/70 backdrop-blur-md px-5 py-2.5 rounded-full shrink-0 relative transition-all"
                         :class="{ 'z-30': open }">
                        <span class="text-xs text-[#876A1A] font-extrabold uppercase tracking-wider">Current Sprint</span>
                        
                        <div class="relative">
                            <!-- Toggle Button -->
                            <button x-on:click="open = !open" 
                                    class="bg-[#FDCB40] text-[#604B10] px-5 py-1.5 rounded-full text-sm font-black outline-none cursor-pointer border-none pr-10 flex items-center select-none hover:bg-[#FDCB40]/90 transition-colors">
                                @if ($selectedSprint)
                                    {{ $selectedSprint->name }}
                                @else
                                    Select Sprint
                                 @endif
                                 
                                 <!-- Custom Chevron -->
                                 <div class="absolute inset-y-0 right-0 flex items-center px-3 text-[#604B10] transition-transform duration-200"
                                      :class="{ 'rotate-180': open }">
                                     <svg class="fill-currentColor h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
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
                                 class="absolute top-[calc(100%+8px)] right-0 min-w-[240px] bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.12)] border border-white/50 m-0 p-1.5 list-none z-[100] flex flex-col gap-1"
                                 style="display: none;">
                                 @foreach ($sprints as $sprint)
                                     <li>
                                         <button x-on:click="open = false; $wire.selectSprint('{{ $sprint->id }}')"
                                                 class="w-full text-left px-4 py-2 text-sm rounded-xl font-bold transition-all duration-150 border-none outline-none cursor-pointer flex items-center justify-between
                                                     {{ $selectedSprintId == $sprint->id ? 'bg-[#FDCB40] text-[#604B10]' : 'text-[#876A1A] hover:bg-[#FDCB40]/20 hover:text-[#604B10]' }}">
                                             <span>{{ $sprint->name }}</span>
                                             <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded
                                                 @if(strtolower($sprint->status) === 'active') bg-green-500/20 text-green-700
                                                 @elseif(strtolower($sprint->status) === 'planned') bg-blue-500/20 text-blue-700
                                                 @else bg-slate-500/20 text-slate-600 @endif">
                                                 {{ $sprint->status }}
                                             </span>
                                         </button>
                                     </li>
                                 @endforeach
                             </ul>
                         </div>
                     </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Selected Sprint Overview -->
    @if ($selectedSprint)
        <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex-grow text-left">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-black text-[#604B10]">{{ $selectedSprint->name }}</h2>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider 
                        @if(strtolower($selectedSprint->status) === 'active') bg-green-500/10 text-green-700 border border-green-500/20
                        @elseif(strtolower($selectedSprint->status) === 'planned') bg-blue-500/10 text-blue-700 border border-blue-500/20
                        @else bg-slate-500/10 text-slate-700 border border-slate-500/20 @endif">
                        @if(strtolower($selectedSprint->status) === 'active')
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-ping"></span>
                        @endif
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

                <!-- Closed Sprint Details, Review and Retro links -->
                @if (strtolower($selectedSprint->status) === 'closed')
                    @if ($selectedSprint->sprintReview)
                        <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm">
                            <span class="font-extrabold text-[#876A1A] text-xs uppercase tracking-wider block mb-1">Sprint Review Summary</span>
                            <p class="text-[#6E5003] font-medium">{{ $selectedSprint->sprintReview->summary }}</p>
                            @if ($selectedSprint->sprintReview->demo_url)
                                <a href="{{ $selectedSprint->sprintReview->demo_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-800 font-bold mt-2 hover:underline">
                                    <x-heroicon-s-link class="w-4 h-4"/>
                                    View Demo Url
                                </a>
                            @endif
                        </div>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('retrospective', ['project' => $activeProject->id, 'sprint' => $selectedSprint->id]) }}" wire:navigate class="px-4 py-2 rounded-full bg-[#604B10] hover:bg-[#604B10]/95 text-white text-xs font-bold flex items-center gap-1.5 no-underline shadow-sm transition">
                            <x-heroicon-s-chat-bubble-left-right class="w-4 h-4 text-[#FDCB40]"/>
                            Sprint Retrospective
                        </a>
                        <a href="{{ route('peer-review', ['project' => $activeProject->id, 'sprint' => $selectedSprint->id]) }}" wire:navigate class="px-4 py-2 rounded-full bg-white border border-[#604B10]/30 hover:bg-[#FDCB40]/15 text-[#604B10] text-xs font-bold flex items-center gap-1.5 no-underline shadow-sm transition">
                            <x-heroicon-s-user-group class="w-4 h-4 text-[#876A1A]"/>
                            Peer Review Cycle
                        </a>
                    </div>
                @endif
            </div>

            @if (strtolower($selectedSprint->status) !== 'closed' && $isOwner)
                <div class="flex items-center gap-3 text-xs font-extrabold shrink-0">
                    @if (strtolower($selectedSprint->status) === 'planned')
                        <button type="button"
                                wire:click="startSprint({{ $selectedSprint->id }})"
                                class="px-5 py-2.5 rounded-full bg-green-600 hover:bg-green-700 text-white text-xs font-extrabold uppercase tracking-wider transition-colors cursor-pointer border-none outline-none shrink-0 flex items-center gap-1.5">
                            <x-heroicon-s-play class="w-4 h-4"/>
                            Start Sprint
                        </button>
                    @elseif (strtolower($selectedSprint->status) === 'active')
                        <button type="button"
                                wire:click="startCloseSprint({{ $selectedSprint->id }})"
                                class="px-5 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold uppercase tracking-wider transition-colors cursor-pointer border-none outline-none shrink-0 flex items-center gap-1.5">
                            <x-heroicon-s-x-circle class="w-4 h-4"/>
                            Close Sprint
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <!-- Kanban Board Layout -->
        @php
            $columns = [
                'selected' => ['title' => 'Backlog', 'accent' => 'border-t-slate-400'],
                'in_progress' => ['title' => 'In Progress', 'accent' => 'border-t-orange-400'],
                'in_review' => ['title' => 'In Review', 'accent' => 'border-t-purple-400'],
                'done' => ['title' => 'Done', 'accent' => 'border-t-green-400'],
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
                     x-bind:class="dragOverColumn === '{{ $columnKey }}' ? 'border-[#FDCB40] bg-[#FDCB40]/10 scale-[1.01]' : 'border-white/40 bg-white/45'">
                    
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
                                    $canMove = $isOwner || ($item->assigned_to_user_id === Auth::id());
                                @endphp

                                <!-- Card item -->
                                <div draggable="{{ $canMove ? 'true' : 'false' }}"
                                     @if($canMove)
                                     x-on:dragstart="startDrag($event, '{{ $item->id }}')"
                                     @endif
                                     class="bg-white p-4.5 rounded-2xl transition-all duration-150 select-none text-left
                                         {{ $canMove ? 'cursor-grab active:cursor-grabbing hover:-translate-y-1 hover:border-[#FDCB40]/40' : 'opacity-70 cursor-not-allowed' }}">
                                     
                                     <div class="flex items-start justify-between gap-2 mb-2">
                                         <!-- Type Tag -->
                                         @if (strtolower($item->type) === 'bug')
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold font-white uppercase tracking-wider bg-rose-500/10">
                                                 Bug
                                             </span>
                                         @elseif (strtolower($item->type) === 'chore')
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold font-white uppercase tracking-wider bg-blue-500/10">
                                                 Chore
                                             </span>
                                         @else
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[0.65rem] font-bold font-white uppercase tracking-wider bg-orange-500/10">
                                                 Story
                                             </span>
                                         @endif

                                         <!-- Lock indicator if non-movable -->
                                         @if (!$canMove)
                                             <span class="text-rose-600 shrink-0" title="Locked: Assigned to another user">
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
                                         <span class="text-[0.7rem] font-bold text-white bg-[#876A1A] px-2 py-0.5 rounded-full">
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
    @else
        <!-- Empty Sprints state -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl border border-white/50 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-calendar class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Sprints Found</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                There are no sprints defined for this project. Sprints organize backlog items into dedicated timelines. Create a sprint to activate the board!
            </p>
        </div>
    @endif

    <!-- Daily Check-in Modal -->
    @if ($showCheckinModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showCheckinModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Daily standup check-in</h3>

                <form wire:submit.prevent="submitCheckin" class="space-y-5 text-left">
                    <!-- Yesterday -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What did you complete yesterday?</label>
                        <textarea wire:model="checkinYesterday" rows="2" placeholder="e.g. Worked on invitations accett flow" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('checkinYesterday') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Today -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What will you work on today?</label>
                        <textarea wire:model="checkinToday" rows="2" placeholder="e.g. Connecting dashboard project settings UI" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('checkinToday') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Blockers -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What is blocking you? (Optional)</label>
                        <textarea wire:model="checkinBlockers" rows="2" placeholder="Describe any roadblocks. This will automatically report an impediment." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('checkinBlockers') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
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
                        @error('checkinConfidence') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-[#6E5003]/10">
                        <button type="button" wire:click="$set('showCheckinModal', false)" class="px-5 py-2.5 rounded-full border border-[#6E5003]/20 bg-white text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/10 transition cursor-pointer outline-none">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                            Submit Check-in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Standup History Modal -->
    @if ($showHistoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-2xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showHistoryModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Standup check-in history</h3>

                @if (count($checkinsHistory) === 0)
                    <p class="text-sm text-slate-500 italic py-6 text-center">No standup check-ins have been submitted for this sprint yet.</p>
                @else
                    <div class="space-y-4 text-left">
                        @foreach ($checkinsHistory as $chk)
                            <div class="bg-[#FDCB40]/5 border border-[#FDCB40]/15 p-5 rounded-2xl">
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
                                        <span class="px-2 py-0.5 rounded bg-[#604B10]/10 font-bold">Conf: {{ $chk->confidence_score }}/5</span>
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
                                        <p class="p-2.5 bg-rose-50 text-rose-700 border border-rose-100 rounded-xl mt-2"><strong class="text-rose-800">Roadblock:</strong> {{ $chk->blockers }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Impediments (Blockers) Modal -->
    @if ($showImpedimentsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-2xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showImpedimentsModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <div class="flex items-center justify-between pb-4 mb-6 border-b border-[#6E5003]/10">
                    <h3 class="text-2xl font-black text-[#604B10]">Impediments & Blockers</h3>
                    <button wire:click="$toggle('showCreateImpediment')" class="text-xs font-bold text-[#604B10] bg-[#FDCB40]/20 px-3 py-1.5 rounded-full hover:bg-[#FDCB40]/40 transition border-none outline-none cursor-pointer">
                        {{ $showCreateImpediment ? 'View Blockers' : 'Report Blocker' }}
                    </button>
                </div>

                @if ($showCreateImpediment)
                    <!-- Create Blocker Form -->
                    <form wire:submit.prevent="saveImpediment" class="space-y-4 text-left">
                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Blocker Title</label>
                            <input type="text" wire:model="newImpedimentTitle" placeholder="e.g. Slow local webpack build" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                            @error('newImpedimentTitle') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Blocker Description</label>
                            <textarea wire:model="newImpedimentDescription" rows="3" placeholder="Describe the blockers in detail so the Scrum master can help resolve it..." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                            @error('newImpedimentDescription') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-3">
                            <button type="button" wire:click="$set('showCreateImpediment', false)" class="px-5 py-2.5 rounded-full border border-[#6E5003]/20 bg-white text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/10 transition cursor-pointer outline-none">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                                Report Blocker
                            </button>
                        </div>
                    </form>
                @else
                    <!-- Blockers List -->
                    @if (count($projectImpediments) === 0)
                        <p class="text-sm text-slate-500 italic py-6 text-center">No blockers have been reported for this project.</p>
                    @else
                        <div class="space-y-4 text-left">
                            @foreach ($projectImpediments as $imp)
                                <div class="border p-4.5 rounded-2xl transition-all duration-300 {{ $imp->status === 'resolved' ? 'bg-slate-50 opacity-60 border-slate-200' : 'bg-rose-50 border-rose-200' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="font-extrabold text-base leading-snug {{ $imp->status === 'resolved' ? 'text-slate-600 line-through' : 'text-rose-950' }}">{{ $imp->title }}</h4>
                                            @if ($imp->description)
                                                <p class="text-xs mt-1.5 {{ $imp->status === 'resolved' ? 'text-slate-500' : 'text-rose-800' }}">{{ $imp->description }}</p>
                                            @endif
                                            <p class="text-[10px] text-slate-500 font-semibold mt-2.5">
                                                Reported by {{ $imp->reporter->name }} {{ $imp->created_at->diffForHumans() }}
                                                @if ($imp->status === 'resolved')
                                                    | Resolved {{ $imp->resolved_at ? $imp->resolved_at->diffForHumans() : '' }}
                                                @endif
                                            </p>
                                        </div>

                                        @if ($imp->status === 'open')
                                            <button wire:click="resolveImpediment({{ $imp->id }})" class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase bg-emerald-600 hover:bg-emerald-700 text-white transition border-none cursor-pointer outline-none shrink-0">
                                                Resolve
                                            </button>
                                        @else
                                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-200 text-slate-600 shrink-0">
                                                Resolved
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <!-- Sprint Review Modal -->
    @if ($showReviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-2xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
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
                        @error('reviewSummary') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Demo URL -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Demo URL (Optional)</label>
                        <input type="url" wire:model="reviewDemoUrl" placeholder="https://demo.example.com" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl border-none outline-none focus:bg-[#FDCB40]/20 font-semibold" />
                        @error('reviewDemoUrl') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Review items decision table -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-3">Item Acceptance Decisions</label>
                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            @foreach ($reviewItems as $index => $itemData)
                                <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                                        <span class="font-extrabold text-sm text-[#604B10]">{{ $itemData['title'] }} ({{ $itemData['points'] }} pts)</span>
                                        <select wire:model="reviewItems.{{ $index }}.decision" class="bg-white text-xs font-bold border border-slate-200 px-3 py-1.5 rounded-lg cursor-pointer outline-none">
                                            <option value="accepted">Accepted (Done)</option>
                                            <option value="carry_over">Carry Over (Backlog)</option>
                                            <option value="rejected">Rejected (Backlog)</option>
                                        </select>
                                    </div>
                                    <input type="text" wire:model="reviewItems.{{ $index }}.notes" placeholder="Notes (e.g. Minor tweak required in styling)" class="w-full bg-white text-xs font-semibold px-3 py-2 border border-slate-100 rounded-lg outline-none" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-[#6E5003]/10">
                        <button type="button" wire:click="$set('showReviewModal', false)" class="px-5 py-2.5 rounded-full border border-[#6E5003]/20 bg-white text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/10 transition cursor-pointer outline-none">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-sm font-extrabold hover:shadow-md transition cursor-pointer border-none outline-none">
                            Submit Review & Close Sprint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
