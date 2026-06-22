<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $backlogItems = [];

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
                ->orderByRaw("CASE 
                    WHEN priority = 'highest' THEN 1 
                    WHEN priority = 'high' THEN 2 
                    WHEN priority = 'medium' THEN 3 
                    WHEN priority = 'low' THEN 4 
                    WHEN priority = 'lowest' THEN 5 
                    ELSE 6 
                END ASC, id DESC")
                ->with('assignedTo')
                ->get();
        } else {
            $this->backlogItems = collect();
        }
    }

    public function deleteItem($itemId)
    {
        $user = Auth::user();
        if ($user->cannot('manageBacklog', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to delete backlog items.', type: 'danger');
            return;
        }

        $item = \App\Models\BacklogItem::find($itemId);
        if ($item && $item->project_id === $this->activeProject->id) {
            $item->delete();
            $this->dispatch('toast', message: 'Backlog item deleted successfully.', type: 'success');
            $this->mount();
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
        
        @if ($activeProject && auth()->user()->can('manageBacklog', $activeProject))
            <div>
                <a href="{{ route('backlog.create') }}?project_id={{ $activeProject->id }}" 
                   wire:navigate
                   class="px-5 py-2.5 rounded-full bg-white text-[#604B10] text-sm font-extrabold flex items-center gap-1.5 cursor-pointer no-underline">
                    <x-heroicon-s-plus class="w-4 h-4"/>
                    Add Item
                </a>
            </div>
        @endif
    </div>

    <!-- Backlog Items List -->
    @if ($activeProject)
        @if (!$backlogItems->isEmpty())
            <div class="space-y-4">
                @foreach ($backlogItems as $item)
                    <div class="bg-white/85 backdrop-blur-md p-5 rounded-2xl shadow-sm transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <!-- Left Info: Type and Title -->
                        <div class="flex items-start gap-3.5 flex-grow">
                            <!-- Type Indicator Badge/Icon -->
                            <div class="shrink-0 mt-0.5">
                                @if (strtolower($item->type) === 'bug')
                                    <span class="w-9 h-9 rounded-xl bg-[#604B10] text-white flex items-center justify-center" title="Bug">
                                        <x-heroicon-s-bug-ant class="w-5 h-5"/>
                                    </span>
                                @elseif (strtolower($item->type) === 'chore')
                                    <span class="w-9 h-9 rounded-xl bg-[#604B10]/10 text-[#604B10] flex items-center justify-center" title="Chore">
                                        <x-heroicon-s-cog-6-tooth class="w-5 h-5"/>
                                    </span>
                                @else
                                    <span class="w-9 h-9 rounded-xl bg-[#FDCB40] text-[#604B10] flex items-center justify-center" title="User Story">
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
                            <!-- Priority Badge -->
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                                @if($item->priority === 'highest') bg-[#604B10] text-white
                                @elseif($item->priority === 'high') bg-[#FDCB40] text-[#604B10]
                                @else bg-[#604B10]/10 text-[#604B10] @endif" title="Priority">
                                {{ $item->priority ?: 'medium' }}
                            </span>

                            <!-- Estimate Points -->
                            @if ($item->estimate_points)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#6E5003]/10 text-[#604B10]" title="Estimate Points">
                                    {{ $item->estimate_points }} pts
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#6E5003]/5 text-[#6E5003]/50 italic">
                                    unestimated
                                </span>
                            @endif

                            <!-- Status Badge -->
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider 
                                @if(strtolower($item->status) === 'done') bg-[#FDCB40] text-[#604B10]
                                @else bg-[#604B10]/10 text-[#604B10] @endif">
                                {{ str_replace('_', ' ', $item->status) }}
                            </span>

                            <!-- Assignee Avatar -->
                            <div class="w-8 h-8 rounded-full bg-[#FDCB40]/20 text-[#604B10] font-black text-xs flex items-center justify-center overflow-hidden" title="{{ $item->assignedTo ? 'Assigned to: ' . $item->assignedTo->name : 'Unassigned' }}">
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

                            <!-- Edit & Delete Buttons -->
                            @if (auth()->user()->can('manageBacklog', $activeProject))
                                <div class="flex items-center gap-2 pl-3">
                                    <a href="{{ route('backlog.edit', $item->id) }}" wire:navigate class="p-1.5 bg-[#FDCB40]/10 text-[#604B10] rounded-lg hover:bg-[#FDCB40]/25 transition border-none cursor-pointer outline-none flex items-center justify-center no-underline" title="Edit Item">
                                        <x-heroicon-s-pencil class="w-4 h-4"/>
                                    </a>
                                    <button wire:click="deleteItem({{ $item->id }})" onclick="confirm('Are you sure you want to delete this item?') || event.stopImmediatePropagation()" class="p-1.5 bg-[#604B10]/10 text-[#604B10] rounded-lg hover:bg-[#604B10]/25 transition border-none cursor-pointer outline-none" title="Delete Item">
                                        <x-heroicon-s-trash class="w-4 h-4"/>
                                    </button>
                                </div>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty Backlog -->
            <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                    <x-heroicon-s-numbered-list class="w-8 h-8"/>
                </div>
                <h3 class="text-xl font-black text-[#604B10]">Your Backlog is Empty</h3>
                <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                    Start by adding items to plan tasks, user stories, or track bugs!
                </p>
                @if (auth()->user()->can('manageBacklog', $activeProject))
                <div class="pt-2">
                    <a href="{{ route('backlog.create') }}?project_id={{ $activeProject->id }}"
                       wire:navigate
                       class="inline-flex px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition-colors cursor-pointer flex items-center justify-center gap-2 no-underline">
                        <x-heroicon-s-plus class="w-4 h-4"/>
                        Add Your First Item
                    </a>
                </div>
                @endif
            </div>
        @endif
    @else
        <!-- No project chosen -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl text-center space-y-4">
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
