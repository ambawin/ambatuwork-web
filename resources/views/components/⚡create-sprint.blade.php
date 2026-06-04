<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintItem;
use App\Models\BacklogItem;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $name = '';
    public $sprint_goal = '';
    public $start_date = '';
    public $end_date = '';
    public $backlog_item_ids = [];

    public $eligibleItems = [];
    public $hasActiveSprint = false;

    public function mount()
    {
        $user = Auth::user();
        $projectId = request()->query('project_id') ?: session('active_project_id');

        if ($projectId) {
            $this->activeProject = Project::visibleTo($user)->find($projectId);
        }

        if (!$this->activeProject) {
            $allProjects = Project::visibleTo($user)->latest()->get();
            if (!$allProjects->isEmpty()) {
                $this->activeProject = $allProjects->first();
                session(['active_project_id' => $this->activeProject->id]);
            }
        }

        if (!$this->activeProject) {
            $this->dispatch('toast', message: 'Please create a project first.', type: 'danger');
            return $this->redirectRoute('dashboard', navigate: true);
        }

        // Enforce authorization policy: only owner can create sprints
        if ($user->cannot('create', [Sprint::class, $this->activeProject])) {
            $this->dispatch('toast', message: 'Only the project owner can create sprints.', type: 'danger');
            return $this->redirectRoute('sprint-board', navigate: true);
        }

        $this->hasActiveSprint = $this->activeProject->sprints()->where('status', 'active')->exists();

        // Load eligible backlog items
        $this->eligibleItems = $this->activeProject->backlogItems()
            ->where('status', '!=', 'archived')
            ->where('status', '!=', 'done')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sprint_items')
                    ->join('sprints', 'sprints.id', '=', 'sprint_items.sprint_id')
                    ->whereIn('sprints.status', ['active', 'planned'])
                    ->whereColumn('sprint_items.backlog_item_id', 'backlog_items.id');
            })
            ->get();

        // Prepopulate dates
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addDays($this->activeProject->default_sprint_length_days ?? 14)->format('Y-m-d');
        
        // Default sprint name based on sprint count
        $sprintCount = $this->activeProject->sprints()->count();
        $this->name = 'Sprint ' . ($sprintCount + 1);
    }

    public function save()
    {
        $user = Auth::user();

        // Enforce authorization policy: only owner can create sprints
        if ($user->cannot('create', [Sprint::class, $this->activeProject])) {
            $this->dispatch('toast', message: 'Only the project owner can create sprints.', type: 'danger');
            return $this->redirectRoute('sprint-board', navigate: true);
        }

        if ($this->hasActiveSprint) {
            $this->dispatch('toast', message: 'Only one active sprint is allowed per project.', type: 'danger');
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'sprint_goal' => 'required|string|max:5000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'backlog_item_ids' => 'required|array|min:1',
            'backlog_item_ids.*' => 'integer|distinct',
        ], [
            'backlog_item_ids.required' => 'You must select at least one backlog item for the sprint.',
        ]);

        // Double check backlog items validity
        $backlogItems = BacklogItem::query()
            ->where('project_id', $this->activeProject->id)
            ->whereIn('id', $this->backlog_item_ids)
            ->where('status', '!=', 'archived')
            ->get();

        if ($backlogItems->count() !== count(array_unique($this->backlog_item_ids))) {
            $this->addError('backlog_item_ids', 'One or more backlog items are invalid for this project.');
            return;
        }

        $sprint = DB::transaction(function () use ($user, $backlogItems) {
            $sprint = $this->activeProject->sprints()->create([
                'name' => $this->name,
                'sprint_goal' => $this->sprint_goal,
                'status' => 'planned',
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'created_by_user_id' => $user->id,
            ]);

            foreach ($backlogItems as $backlogItem) {
                SprintItem::create([
                    'sprint_id' => $sprint->id,
                    'backlog_item_id' => $backlogItem->id,
                    'committed_points' => $backlogItem->estimate_points,
                    'added_by_user_id' => $user->id,
                    'added_at' => now(),
                ]);

                $backlogItem->update([
                    'status' => 'selected',
                ]);
            }

            return $sprint;
        });

        $this->dispatch('toast', message: 'Sprint created successfully.', type: 'success');

        return $this->redirectRoute('sprint-board', navigate: true);
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Circular Back Button -->
    <div class="mb-8">
        <a href="{{ route('sprint-board') }}" 
           wire:navigate
           class="inline-flex w-12 h-12 rounded-full bg-white text-[#604B10] items-center justify-center hover:bg-white/90 transition-colors select-none cursor-pointer outline-none border-none">
            <x-heroicon-s-arrow-left class="w-6 h-6"/>
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Create New Sprint</h1>
            <p class="text-sm text-[#876A1A] mt-1">Plan a new sprint cycle for <span class="font-extrabold text-[#604B10]">{{ $activeProject->name }}</span>.</p>
        </div>

        <!-- Active Sprint Warning -->
        @if ($hasActiveSprint)
            <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 text-amber-900 text-sm flex items-start gap-3">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 shrink-0 mt-0.5 text-amber-700"/>
                <div>
                    <span class="font-bold">Active Sprint Exists:</span>
                    <p class="mt-1 font-medium">An active sprint is currently running in this project. You must close the active sprint on the Sprint Board before you can start or plan a new one.</p>
                </div>
            </div>
        @endif

        <form wire:submit="save" class="bg-white p-8 rounded-3xl space-y-6">
            <!-- Sprint Name -->
            <div>
                <label for="name" class="block text-sm font-bold text-[#6E5003] mb-2">Sprint Name</label>
                <input type="text" id="name" wire:model="name" placeholder="e.g. Sprint 1"
                       class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                @error('name') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Sprint Goal -->
            <div>
                <label for="sprint_goal" class="block text-sm font-bold text-[#6E5003] mb-2">Sprint Goal</label>
                <textarea id="sprint_goal" wire:model="sprint_goal" rows="3" placeholder="What is the key goal/focus for this sprint cycle?"
                          class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                @error('sprint_goal') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-sm font-bold text-[#6E5003] mb-2">Start Date</label>
                    <input type="date" id="start_date" wire:model="start_date"
                           class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                    @error('start_date') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-sm font-bold text-[#6E5003] mb-2">End Date</label>
                    <input type="date" id="end_date" wire:model="end_date"
                           class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                    @error('end_date') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Backlog Items Selection -->
            <div>
                <label class="block text-sm font-bold text-[#6E5003] mb-2">Select Backlog Items</label>
                
                @if(!$eligibleItems->isEmpty())
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        @foreach ($eligibleItems as $item)
                            <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-[#FDCB40]/5 hover:bg-[#FDCB40]/10 transition-colors cursor-pointer select-none">
                                <input type="checkbox" wire:model="backlog_item_ids" value="{{ $item->id }}" 
                                       class="mt-1 accent-[#604B10] cursor-pointer" />
                                <div>
                                    <span class="font-extrabold text-sm text-[#604B10] block">{{ $item->title }}</span>
                                    <span class="text-xs text-[#876A1A]/90 font-bold block mt-0.5">
                                        {{ ucfirst($item->type) }} — {{ $item->estimate_points ?? 0 }} pts — {{ $item->business_value ?? 0 }} BV
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 bg-[#FDCB40]/10 rounded-2xl text-center">
                        <p class="text-sm font-bold text-[#876A1A]">No backlog items are available for a new sprint.</p>
                        <p class="text-xs text-[#876A1A]/70 mt-1">Make sure items exist in the backlog, and are not completed or already in another planned/active sprint.</p>
                    </div>
                @endif
                @error('backlog_item_ids') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit" 
                        @if($hasActiveSprint) disabled @endif
                        class="w-full bg-[#FDCB40] text-[#604B10] px-6 py-4 rounded-full font-black hover:bg-[#FDCB40]/90 transition-colors cursor-pointer border-none outline-none text-center disabled:opacity-50 disabled:cursor-not-allowed">
                    Create Sprint
                </button>
            </div>
        </form>
    </div>
</div>
