<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\DefinitionOfDone;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $name = '';
    public $description = '';
    public $product_goal = '';
    public $default_sprint_length_days = 14;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:2000',
        'product_goal' => 'required|string|max:5000',
        'default_sprint_length_days' => 'required|integer|min:1|max:30',
    ];

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        $project = DB::transaction(function () use ($user) {
            $project = Project::create([
                'owner_user_id' => $user->id,
                'name' => $this->name,
                'description' => $this->description ?: null,
                'product_goal' => $this->product_goal,
                'default_sprint_length_days' => (int) $this->default_sprint_length_days,
                'wip_limit_per_member' => null,
                'status' => 'active',
            ]);

            $project->memberships()->create([
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $project->definitionsOfDone()->create([
                'title' => DefinitionOfDone::defaultTitle(),
                'checklist' => DefinitionOfDone::defaultChecklist(),
                'is_active' => true,
                'created_by_user_id' => $user->id,
            ]);

            return $project;
        });

        session(['active_project_id' => $project->id]);

        $this->dispatch('toast', message: 'Project created successfully.', type: 'success');

        return $this->redirectRoute('dashboard', navigate: true);
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Circular Back Button -->
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" 
           wire:navigate
           class="inline-flex w-12 h-12 rounded-full bg-white text-[#604B10] items-center justify-center hover:bg-white/90 transition-colors select-none cursor-pointer outline-none border-none">
            <x-heroicon-s-arrow-left class="w-6 h-6"/>
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Create New Project</h1>
            <p class="text-sm text-[#876A1A] mt-1">Start a new Scrum workspace and define your product goal.</p>
        </div>

        <form wire:submit="save" class="bg-white p-8 rounded-3xl space-y-6">
            <!-- Project Name -->
            <div>
                <label for="name" class="block text-sm font-bold text-[#6E5003] mb-2">Project Name</label>
                <input type="text" id="name" wire:model="name" placeholder="e.g. Marketing Website Revamp"
                       class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                @error('name') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Product Goal -->
            <div>
                <label for="product_goal" class="block text-sm font-bold text-[#6E5003] mb-2">Product Goal</label>
                <textarea id="product_goal" wire:model="product_goal" rows="3" placeholder="Define a clear, high-level goal for the product..."
                          class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                @error('product_goal') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Default Sprint Length -->
            <div>
                <label for="default_sprint_length_days" class="block text-sm font-bold text-[#6E5003] mb-2">Default Sprint Length (Days)</label>
                <input type="number" id="default_sprint_length_days" wire:model="default_sprint_length_days" min="1" max="30"
                       class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                @error('default_sprint_length_days') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-bold text-[#6E5003] mb-2">Description (Optional)</label>
                <textarea id="description" wire:model="description" rows="4" placeholder="Briefly describe the project scope or details..."
                          class="w-full bg-[#FDCB40]/10 text-[#604B10] px-5 py-3.5 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                @error('description') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit"
                        class="w-full bg-[#FDCB40] text-[#604B10] px-6 py-4 rounded-full font-black hover:bg-[#FDCB40]/90 transition-colors cursor-pointer border-none outline-none text-center">
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>
