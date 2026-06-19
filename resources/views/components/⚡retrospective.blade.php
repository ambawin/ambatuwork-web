<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $project;
    public $sprint;
    public $retrospective;
    public $teamHappinessScore = 3;
    public $groupedItems = [];
    public $isOwner = false;

    // Card form properties
    public $newCardBody = '';
    public $newCardType = 'went_well';
    public $newCardAssigneeId = '';
    
    // Edit card properties
    public $showEditModal = false;
    public $editCardId;
    public $editCardBody = '';
    public $editCardType = 'went_well';
    public $editCardAssigneeId = '';

    public $members = [];

    public function mount(\App\Models\Project $project, \App\Models\Sprint $sprint)
    {
        $this->project = $project;
        $this->sprint = $sprint;

        $user = Auth::user();
        if (!$project->isAccessibleTo($user)) {
            abort(403);
        }

        $this->isOwner = $project->isOwnedBy($user) || $project->roleFor($user) === 'owner';
        $this->members = $project->members()->where('status', 'active')->get();

        // Load or create retro
        $this->retrospective = \App\Models\Retrospective::firstOrCreate(
            ['sprint_id' => $sprint->id],
            ['project_id' => $project->id]
        );

        $this->teamHappinessScore = $this->retrospective->team_happiness_score ?: 3;
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->retrospective->refresh();
        $items = $this->retrospective->items()->with(['author', 'assignee'])->get();

        $this->groupedItems = [
            'went_well' => $items->where('type', 'went_well')->values(),
            'to_improve' => $items->where('type', 'to_improve')->values(),
            'action_item' => $items->where('type', 'action_item')->values(),
        ];
    }

    public function updateHappinessScore()
    {
        $this->retrospective->update([
            'team_happiness_score' => (int) $this->teamHappinessScore,
        ]);
        $this->dispatch('toast', message: 'Happiness score updated.', type: 'success');
    }

    public function addCard()
    {
        $this->newCardBody = trim($this->newCardBody);
        if ($this->newCardBody === '') {
            return;
        }

        $this->validate([
            'newCardBody' => 'required|string|max:1000',
            'newCardType' => 'required|string|in:went_well,to_improve,action_item',
            'newCardAssigneeId' => 'nullable|exists:users,id',
        ]);

        $this->retrospective->items()->create([
            'author_user_id' => Auth::id(),
            'body' => $this->newCardBody,
            'type' => $this->newCardType,
            'assigned_to_user_id' => $this->newCardAssigneeId ?: null,
            'is_completed' => false,
        ]);

        $this->newCardBody = '';
        $this->newCardAssigneeId = '';
        $this->loadItems();
        $this->dispatch('toast', message: 'Retro item added.', type: 'success');
    }

    public function openEditCard($id)
    {
        $item = \App\Models\RetroItem::find($id);
        if (!$item || $item->retrospective_id !== $this->retrospective->id) return;

        // User can only update their own items unless they are the project owner
        if (Auth::id() !== $item->author_user_id && !$this->isOwner) {
            $this->dispatch('toast', message: 'You can only edit items you created.', type: 'danger');
            return;
        }

        $this->editCardId = $item->id;
        $this->editCardBody = $item->body;
        $this->editCardType = $item->type;
        $this->editCardAssigneeId = $item->assigned_to_user_id ?: '';
        $this->showEditModal = true;
    }

    public function updateCard()
    {
        $item = \App\Models\RetroItem::find($this->editCardId);
        if (!$item || $item->retrospective_id !== $this->retrospective->id) return;

        if (Auth::id() !== $item->author_user_id && !$this->isOwner) {
            $this->dispatch('toast', message: 'You can only edit items you created.', type: 'danger');
            return;
        }

        $this->validate([
            'editCardBody' => 'required|string|max:1000',
            'editCardType' => 'required|string|in:went_well,to_improve,action_item',
            'editCardAssigneeId' => 'nullable|exists:users,id',
        ]);

        $item->update([
            'body' => $this->editCardBody,
            'type' => $this->editCardType,
            'assigned_to_user_id' => $this->editCardAssigneeId ?: null,
        ]);

        $this->showEditModal = false;
        $this->loadItems();
        $this->dispatch('toast', message: 'Retro item updated.', type: 'success');
    }

    public function deleteCard($id)
    {
        $item = \App\Models\RetroItem::find($id);
        if (!$item || $item->retrospective_id !== $this->retrospective->id) return;

        if (Auth::id() !== $item->author_user_id && !$this->isOwner) {
            $this->dispatch('toast', message: 'You can only delete items you created.', type: 'danger');
            return;
        }

        $item->delete();
        $this->loadItems();
        $this->dispatch('toast', message: 'Retro item deleted.', type: 'success');
    }

    public function toggleCompleted($id)
    {
        $item = \App\Models\RetroItem::find($id);
        if (!$item || $item->retrospective_id !== $this->retrospective->id) return;

        $item->update([
            'is_completed' => !$item->is_completed,
        ]);
        $this->loadItems();
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center gap-4 text-left">
        <a href="{{ route('sprint-board') }}?project_id={{ $project->id }}" 
           wire:navigate
           class="inline-flex w-12 h-12 rounded-full bg-white text-[#604B10] items-center justify-center hover:bg-white/90 transition-colors select-none cursor-pointer outline-none border-none shrink-0">
            <x-heroicon-s-arrow-left class="w-6 h-6"/>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Sprint Retrospective</h1>
            <p class="text-sm text-[#876A1A] mt-1">
                Reflecting on <span class="font-extrabold text-[#604B10]">{{ $sprint->name }}</span> for project <span class="font-extrabold text-[#604B10]">{{ $project->name }}</span>.
            </p>
        </div>
    </div>

    <!-- Happiness Score Card -->
    <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6 text-left border border-white/50">
        <div>
            <h3 class="font-extrabold text-lg text-[#604B10] flex items-center gap-1.5">
                <x-heroicon-s-face-smile class="w-5 h-5 text-amber-500"/>
                Team Happiness Score
            </h3>
            <p class="text-xs text-[#876A1A] mt-1">Rate the team morale and happiness during this sprint (1-5).</p>
        </div>
        <div class="flex items-center gap-4 w-full sm:w-80 shrink-0">
            <input type="range" wire:model="teamHappinessScore" wire:change="updateHappinessScore" min="1" max="5" class="w-full accent-[#604B10] cursor-pointer" />
            <span class="text-2xl font-black text-[#604B10] bg-[#FDCB40]/20 px-4 py-1.5 rounded-2xl shrink-0">
                {{ $teamHappinessScore }}/5
            </span>
        </div>
    </div>

    <!-- Retro Board Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        
        <!-- COLUMN 1: Went Well -->
        <div class="bg-white/85 backdrop-blur-md p-5 rounded-3xl border border-white/50 shadow-sm min-h-[500px]">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#6E5003]/10">
                <span class="font-black text-sm text-[#604B10] uppercase tracking-wider flex items-center gap-1.5">
                    <x-heroicon-s-hand-thumb-up class="w-4 h-4"/>
                    Went Well
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#FDCB40]/20 text-[#604B10]">
                    {{ count($groupedItems['went_well']) }}
                </span>
            </div>

            <!-- Went Well Quick Add -->
            <div x-data="{ open: false, body: '' }" class="mb-4 text-left">
                <button x-show="!open" x-on:click="open = true" class="w-full py-2.5 rounded-xl border border-[#6E5003]/20 bg-[#FDCB40]/10 text-[#604B10] font-extrabold text-xs hover:bg-[#FDCB40]/25 transition border-dashed cursor-pointer outline-none">
                    + Add Note
                </button>
                <div x-show="open" class="bg-white p-4 rounded-2xl border border-[#6E5003]/15 shadow-sm">
                    <textarea x-model="body" placeholder="What went well..." class="w-full text-xs font-semibold bg-[#FDCB40]/5 p-2.5 rounded-xl border border-[#6E5003]/10 outline-none resize-none focus:ring-1 focus:ring-[#FDCB40]" rows="2" wire:keydown.enter.prevent="if(body.trim() !== '') { $wire.set('newCardBody', body); $wire.set('newCardType', 'went_well'); $wire.addCard(); open = false; body = ''; }"></textarea>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" x-on:click="open = false; body = ''" class="px-3 py-1.5 rounded-lg border border-[#6E5003]/20 text-[#6E5003] text-[10px] font-bold bg-white cursor-pointer">Cancel</button>
                        <button type="button" x-on:click="if(body.trim() !== '') { $wire.set('newCardBody', body); $wire.set('newCardType', 'went_well'); $wire.addCard(); open = false; body = ''; }" class="px-3 py-1.5 rounded-lg bg-[#FDCB40] text-[#604B10] text-[10px] font-bold border-none cursor-pointer hover:bg-[#FDCB40]/90">Add</button>
                    </div>
                </div>
            </div>

            <!-- Went Well Cards -->
            <div class="space-y-3">
                @foreach ($groupedItems['went_well'] as $item)
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm relative group hover:shadow transition text-left">
                        <p class="text-sm font-semibold text-[#6E5003] leading-relaxed">{{ $item->body }}</p>
                        <div class="flex items-center justify-between mt-3 text-[10px] text-slate-400 font-bold">
                            <span>By {{ $item->author->name }}</span>
                            @if (Auth::id() === $item->author_user_id || $isOwner)
                                <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEditCard({{ $item->id }})" class="text-blue-500 hover:text-blue-700 bg-transparent border-none outline-none cursor-pointer">Edit</button>
                                    <span>•</span>
                                    <button wire:click="deleteCard({{ $item->id }})" onclick="confirm('Delete this card?') || event.stopImmediatePropagation()" class="text-rose-500 hover:text-rose-700 bg-transparent border-none outline-none cursor-pointer">Delete</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- COLUMN 2: To Improve -->
        <div class="bg-white/85 backdrop-blur-md p-5 rounded-3xl border border-white/50 shadow-sm min-h-[500px]">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#6E5003]/10">
                <span class="font-black text-sm text-[#604B10] uppercase tracking-wider flex items-center gap-1.5">
                    <x-heroicon-s-exclamation-circle class="w-4 h-4"/>
                    To Improve
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#FDCB40]/20 text-[#604B10]">
                    {{ count($groupedItems['to_improve']) }}
                </span>
            </div>

            <!-- To Improve Quick Add -->
            <div x-data="{ open: false, body: '' }" class="mb-4 text-left">
                <button x-show="!open" x-on:click="open = true" class="w-full py-2.5 rounded-xl border border-[#6E5003]/20 bg-[#FDCB40]/10 text-[#604B10] font-extrabold text-xs hover:bg-[#FDCB40]/25 transition border-dashed cursor-pointer outline-none">
                    + Add Note
                </button>
                <div x-show="open" class="bg-white p-4 rounded-2xl border border-[#6E5003]/15 shadow-sm">
                    <textarea x-model="body" placeholder="What needs improvement..." class="w-full text-xs font-semibold bg-[#FDCB40]/5 p-2.5 rounded-xl border border-[#6E5003]/10 outline-none resize-none focus:ring-1 focus:ring-[#FDCB40]" rows="2" wire:keydown.enter.prevent="if(body.trim() !== '') { $wire.set('newCardBody', body); $wire.set('newCardType', 'to_improve'); $wire.addCard(); open = false; body = ''; }"></textarea>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" x-on:click="open = false; body = ''" class="px-3 py-1.5 rounded-lg border border-[#6E5003]/20 text-[#6E5003] text-[10px] font-bold bg-white cursor-pointer">Cancel</button>
                        <button type="button" x-on:click="if(body.trim() !== '') { $wire.set('newCardBody', body); $wire.set('newCardType', 'to_improve'); $wire.addCard(); open = false; body = ''; }" class="px-3 py-1.5 rounded-lg bg-[#FDCB40] text-[#604B10] text-[10px] font-bold border-none cursor-pointer hover:bg-[#FDCB40]/90">Add</button>
                    </div>
                </div>
            </div>

            <!-- To Improve Cards -->
            <div class="space-y-3">
                @foreach ($groupedItems['to_improve'] as $item)
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm relative group hover:shadow transition text-left">
                        <p class="text-sm font-semibold text-[#6E5003] leading-relaxed">{{ $item->body }}</p>
                        <div class="flex items-center justify-between mt-3 text-[10px] text-slate-400 font-bold">
                            <span>By {{ $item->author->name }}</span>
                            @if (Auth::id() === $item->author_user_id || $isOwner)
                                <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEditCard({{ $item->id }})" class="text-blue-500 hover:text-blue-700 bg-transparent border-none outline-none cursor-pointer">Edit</button>
                                    <span>•</span>
                                    <button wire:click="deleteCard({{ $item->id }})" onclick="confirm('Delete this card?') || event.stopImmediatePropagation()" class="text-rose-500 hover:text-rose-700 bg-transparent border-none outline-none cursor-pointer">Delete</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- COLUMN 3: Action Items -->
        <div class="bg-white/85 backdrop-blur-md p-5 rounded-3xl border border-white/50 shadow-sm min-h-[500px]">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#6E5003]/10">
                <span class="font-black text-sm text-[#604B10] uppercase tracking-wider flex items-center gap-1.5">
                    <x-heroicon-s-check-circle class="w-4 h-4"/>
                    Action Items
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#FDCB40]/20 text-[#604B10]">
                    {{ count($groupedItems['action_item']) }}
                </span>
            </div>

            <!-- Action Items Quick Add -->
            <div x-data="{ open: false, body: '', assigneeId: '' }" class="mb-4 text-left">
                <button x-show="!open" x-on:click="open = true" class="w-full py-2.5 rounded-xl border border-[#6E5003]/20 bg-[#FDCB40]/10 text-[#604B10] font-extrabold text-xs hover:bg-[#FDCB40]/25 transition border-dashed cursor-pointer outline-none">
                    + Add Action Item
                </button>
                <div x-show="open" class="bg-white p-4 rounded-2xl border border-[#6E5003]/15 shadow-sm">
                    <textarea x-model="body" placeholder="Action item description..." class="w-full text-xs font-semibold bg-[#FDCB40]/5 p-2.5 rounded-xl border border-[#6E5003]/10 outline-none resize-none focus:ring-1 focus:ring-[#FDCB40]" rows="2"></textarea>
                    
                    <select x-model="assigneeId" class="w-full text-[10px] bg-[#FDCB40]/5 border border-[#6E5003]/10 p-2 rounded-xl mt-2 cursor-pointer outline-none font-bold text-[#604B10]">
                        <option value="">Select Assignee (Optional)</option>
                        @foreach ($members as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>

                    <div class="flex justify-end gap-2 mt-3">
                        <button type="button" x-on:click="open = false; body = ''; assigneeId = ''" class="px-3 py-1.5 rounded-lg border border-[#6E5003]/20 text-[#6E5003] text-[10px] font-bold bg-white cursor-pointer">Cancel</button>
                        <button type="button" x-on:click="if(body.trim() !== '') { $wire.set('newCardBody', body); $wire.set('newCardType', 'action_item'); $wire.set('newCardAssigneeId', assigneeId); $wire.addCard(); open = false; body = ''; assigneeId = ''; }" class="px-3 py-1.5 rounded-lg bg-[#FDCB40] text-[#604B10] text-[10px] font-bold border-none cursor-pointer hover:bg-[#FDCB40]/90">Add</button>
                    </div>
                </div>
            </div>

            <!-- Action Items Cards -->
            <div class="space-y-3">
                @foreach ($groupedItems['action_item'] as $item)
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm relative group hover:shadow transition text-left {{ $item->is_completed ? 'bg-slate-50/60 opacity-75' : '' }}">
                        <div class="flex items-start gap-2.5">
                            <input type="checkbox" wire:click="toggleCompleted({{ $item->id }})" {{ $item->is_completed ? 'checked' : '' }} class="mt-1 w-4 h-4 accent-[#604B10] rounded border-slate-300 cursor-pointer" />
                            <div class="flex-grow">
                                <p class="text-sm font-semibold text-[#6E5003] leading-relaxed {{ $item->is_completed ? 'line-through text-slate-500' : '' }}">{{ $item->body }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mt-3.5 text-[10px] text-slate-400 font-bold">
                            <div class="flex flex-col gap-0.5">
                                <span>By {{ $item->author->name }}</span>
                                @if ($item->assignee)
                                    <span class="px-1.5 py-0.5 rounded bg-[#FDCB40]/20 text-[#604B10] font-semibold mt-0.5">Owner: {{ $item->assignee->name }}</span>
                                @endif
                            </div>
                            @if (Auth::id() === $item->author_user_id || $isOwner)
                                <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEditCard({{ $item->id }})" class="text-blue-500 hover:text-blue-700 bg-transparent border-none outline-none cursor-pointer">Edit</button>
                                    <span>•</span>
                                    <button wire:click="deleteCard({{ $item->id }})" onclick="confirm('Delete this card?') || event.stopImmediatePropagation()" class="text-rose-500 hover:text-rose-700 bg-transparent border-none outline-none cursor-pointer">Delete</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- Edit Card Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showEditModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Edit Retro Note</h3>

                <form wire:submit.prevent="updateCard" class="space-y-5 text-left">
                    <!-- Body -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Content</label>
                        <textarea wire:model="editCardBody" rows="4" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('editCardBody') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Column</label>
                        <select wire:model="editCardType" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors appearance-none cursor-pointer">
                            <option value="went_well">Went Well</option>
                            <option value="to_improve">To Improve</option>
                            <option value="action_item">Action Item</option>
                        </select>
                        @error('editCardType') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Assignee (Only for Action Items) -->
                    @if ($editCardType === 'action_item')
                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Assignee</label>
                            <select wire:model="editCardAssigneeId" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors appearance-none cursor-pointer">
                                <option value="">Select Assignee (Optional)</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                            @error('editCardAssigneeId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-[#6E5003]/10">
                        <button type="button" wire:click="$set('showEditModal', false)" class="px-5 py-2.5 rounded-full border border-[#6E5003]/20 bg-white text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/10 transition cursor-pointer outline-none">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
