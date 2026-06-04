<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\BacklogItem;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $activeProject;
    public $title = '';
    public $description = '';
    public $type = 'story';
    public $business_value = 50;
    public $estimate_points = 8;
    public $assigned_to_user_id = '';
    public $acceptance_criteria = [];
    public $newCriteria = '';
    
    public $members = [];

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

        // Enforce authorization policy: manageBacklog
        if ($user->cannot('manageBacklog', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to add backlog items to this project.', type: 'danger');
            return $this->redirectRoute('backlog', navigate: true);
        }

        // Fetch active project members for assignment dropdown
        $this->members = $this->activeProject->members()
            ->where('status', 'active')
            ->get();
    }

    public function addCriteria()
    {
        $this->newCriteria = trim($this->newCriteria);
        if ($this->newCriteria !== '') {
            $this->acceptance_criteria[] = $this->newCriteria;
            $this->newCriteria = '';
        }
    }

    public function removeCriteria($index)
    {
        if (isset($this->acceptance_criteria[$index])) {
            unset($this->acceptance_criteria[$index]);
            $this->acceptance_criteria = array_values($this->acceptance_criteria);
        }
    }

    public function save()
    {
        $user = Auth::user();

        // Enforce authorization policy: manageBacklog
        if ($user->cannot('manageBacklog', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to add backlog items to this project.', type: 'danger');
            return $this->redirectRoute('backlog', navigate: true);
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type' => 'required|string|in:story,task,bug,improvement',
            'business_value' => 'nullable|integer|min:1|max:100',
            'estimate_points' => 'nullable|integer|min:1|max:100',
            'assigned_to_user_id' => 'nullable|exists:project_memberships,user_id,project_id,' . $this->activeProject->id . ',status,active',
        ]);

        $maxPriorityRank = (float) ($this->activeProject->backlogItems()->max('priority_rank') ?? 0);

        $this->activeProject->backlogItems()->create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'status' => 'backlog',
            'priority_rank' => $maxPriorityRank + 1.0,
            'business_value' => $this->business_value !== '' ? (int)$this->business_value : null,
            'estimate_points' => $this->estimate_points !== '' ? (int)$this->estimate_points : null,
            'acceptance_criteria' => !empty($this->acceptance_criteria) ? $this->acceptance_criteria : null,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $this->assigned_to_user_id ?: null,
        ]);

        $this->dispatch('toast', message: 'Backlog item created successfully.', type: 'success');

        return $this->redirectRoute('backlog', navigate: true);
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <style>
        /* Flat Slider Custom styling */
        input[type="range"].flat-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            background: transparent;
            cursor: pointer;
        }
        
        input[type="range"].flat-slider:focus {
            outline: none;
        }
        
        /* WebKit Track */
        input[type="range"].flat-slider::-webkit-slider-runnable-track {
            background: rgba(96, 75, 16, 0.15);
            height: 8px;
            border-radius: 9999px;
        }
        
        /* WebKit Thumb */
        input[type="range"].flat-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            margin-top: -6px;
            background-color: #604B10;
            height: 20px;
            width: 20px;
            border-radius: 9999px;
            border: none;
        }
        
        /* Firefox Track */
        input[type="range"].flat-slider::-moz-range-track {
            background: rgba(96, 75, 16, 0.15);
            height: 8px;
            border-radius: 9999px;
        }
        
        /* Firefox Thumb */
        input[type="range"].flat-slider::-moz-range-thumb {
            background-color: #604B10;
            height: 20px;
            width: 20px;
            border-radius: 9999px;
            border: none;
        }
    </style>
    <!-- Circular Back Button -->
    <div class="mb-8">
        <a href="{{ route('backlog') }}" 
           wire:navigate
           class="inline-flex w-12 h-12 rounded-full bg-white text-[#604B10] items-center justify-center hover:bg-white/90 transition-colors select-none cursor-pointer outline-none border-none">
            <x-heroicon-s-arrow-left class="w-6 h-6"/>
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Create Backlog Item</h1>
            <p class="text-sm text-[#876A1A] mt-1">Add a new user story, task, or bug to the backlog of <span class="font-extrabold text-[#604B10]">{{ $activeProject->name }}</span>.</p>
        </div>

        <form wire:submit="save" class="bg-white p-8 rounded-3xl space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-bold text-[#6E5003] mb-2">Title</label>
                <input type="text" id="title" wire:model="title" placeholder="e.g. As an owner, I want to invite members"
                       class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                @error('title') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-bold text-[#6E5003] mb-2">Type</label>
                <select id="type" wire:model="type"
                        class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors appearance-none cursor-pointer">
                    <option value="story">User Story</option>
                    <option value="task">Task</option>
                    <option value="bug">Bug</option>
                    <option value="improvement">Improvement</option>
                </select>
                @error('type') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Business Value -->
                <div x-data="{ val: @entangle('business_value') }">
                    <div class="flex justify-between items-center mb-2">
                        <label for="business_value" class="block text-sm font-bold text-[#6E5003]">Business Value (1-100)</label>
                        <span class="text-xs font-black text-[#604B10] bg-[#FDCB40]/20 px-2 py-0.5 rounded-lg" x-text="val"></span>
                    </div>
                    <input type="range" id="business_value" x-model="val" min="1" max="100"
                           class="flat-slider w-full outline-none" />
                    @error('business_value') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Estimate Points -->
                <div x-data="{ val: @entangle('estimate_points') }">
                    <div class="flex justify-between items-center mb-2">
                        <label for="estimate_points" class="block text-sm font-bold text-[#6E5003]">Estimate Points (1-100)</label>
                        <span class="text-xs font-black text-[#604B10] bg-[#FDCB40]/20 px-2 py-0.5 rounded-lg" x-text="val"></span>
                    </div>
                    <input type="range" id="estimate_points" x-model="val" min="1" max="100"
                           class="flat-slider w-full outline-none" />
                    @error('estimate_points') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-bold text-[#6E5003] mb-2">Description</label>
                <textarea id="description" wire:model="description" rows="4" placeholder="Describe the item requirements and context..."
                          class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                @error('description') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Assigned To -->
            <div>
                <label for="assigned_to_user_id" class="block text-sm font-bold text-[#6E5003] mb-2">Assignee (Optional)</label>
                <select id="assigned_to_user_id" wire:model="assigned_to_user_id"
                        class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors appearance-none cursor-pointer">
                    <option value="">Unassigned</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to_user_id') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Acceptance Criteria -->
            <div>
                <label class="block text-sm font-bold text-[#6E5003] mb-2">Acceptance Criteria</label>
                
                <!-- List of existing criteria -->
                @if(!empty($acceptance_criteria))
                    <ul class="space-y-2 mb-4">
                        @foreach ($acceptance_criteria as $index => $criteria)
                            <li class="flex items-center justify-between bg-[#FDCB40]/5 px-4 py-2.5 rounded-xl">
                                <span class="text-sm font-medium text-[#6E5003]">{{ $criteria }}</span>
                                <button type="button" wire:click="removeCriteria({{ $index }})" 
                                        class="text-rose-600 hover:text-rose-800 transition-colors cursor-pointer border-none outline-none bg-transparent">
                                    <x-heroicon-s-trash class="w-5 h-5"/>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <!-- Add new criteria input -->
                <div class="flex gap-2">
                    <input type="text" wire:model="newCriteria" placeholder="e.g. Invitee accepts standard invitation"
                           class="flex-grow bg-[#FDCB40]/10 text-[#604B10] px-5 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors"
                           wire:keydown.enter.prevent="addCriteria" />
                    <button type="button" wire:click="addCriteria"
                            class="bg-[#FDCB40] text-[#604B10] px-5 py-3 rounded-2xl font-bold hover:bg-[#FDCB40]/90 transition-colors cursor-pointer border-none outline-none shrink-0 flex items-center justify-center">
                        <x-heroicon-s-plus class="w-5 h-5"/>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit"
                        class="w-full bg-[#FDCB40] text-[#604B10] px-6 py-4 rounded-full font-black hover:bg-[#FDCB40]/90 transition-colors cursor-pointer border-none outline-none text-center">
                    Create Backlog Item
                </button>
            </div>
        </form>
    </div>
</div>
