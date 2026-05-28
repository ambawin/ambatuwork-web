<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $sprints;
    public $selectedSprintId;
    public $selectedSprint;
    public $groupedItems = [];
    public $isOwner = false;

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
                ->with('createdBy')
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

        @if ($activeProject && !$sprints->isEmpty())
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



    <!-- Selected Sprint Overview -->
    @if ($selectedSprint)
        <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
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
                </div>
                @if ($selectedSprint->sprint_goal)
                    <p class="text-xs text-[#876A1A] font-bold mt-1.5 flex items-center gap-1">
                        <x-heroicon-s-trophy class="w-4 h-4"/>
                        Goal: <span class="text-[#6E5003] font-medium italic">{{ $selectedSprint->sprint_goal }}</span>
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-3 text-xs text-[#876A1A] font-extrabold shrink-0">
                <div class="px-3.5 py-2 rounded-2xl flex items-center gap-1.5">
                    <x-heroicon-s-calendar class="w-4 h-4"/>
                    <span>{{ $selectedSprint->start_date->format('M d, Y') }} — {{ $selectedSprint->end_date->format('M d, Y') }}</span>
                </div>
            </div>
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
                    // Delay setting dragItemId to null slightly to avoid race condition with drop event
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
                                     class="bg-white p-4.5 rounded-2xl transition-all duration-150 select-none
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
</div>
