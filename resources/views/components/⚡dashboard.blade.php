<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\ProjectInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DefinitionOfDone;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $user;
    public $ownedProjects;
    public $joinedProjects;
    public $pendingInvitations;
    public $totalProjectsCount;
    public $activeSprintsCount;
    public $activeProject;
    public $isOwner = false;

    // Project Settings & DoD properties
    public $showSettingsModal = false;
    public $editName = '';
    public $editDescription = '';
    public $editProductGoal = '';
    public $editDefaultSprintLength = 14;
    public $editDoDList = [];
    public $newDoDItem = '';

    // Team Management properties
    public $showTeamModal = false;
    public $inviteEmail = '';
    public $inviteRole = 'member';
    public $projectInvitations = [];
    public $teamMembers = [];

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
                ->with(['owner', 'activeSprint', 'members', 'activeDefinitionOfDone'])
                ->find($activeProjectId);
        }
        
        if (!$this->activeProject) {
            $allProjects = \App\Models\Project::visibleTo($this->user)
                ->with(['owner', 'activeSprint', 'members', 'activeDefinitionOfDone'])
                ->latest()
                ->get();
            if (!$allProjects->isEmpty()) {
                $this->activeProject = $allProjects->first();
                session(['active_project_id' => $this->activeProject->id]);
            }
        }

        if ($this->activeProject) {
            $this->isOwner = $this->activeProject->owner_user_id === $this->user->id;
            $this->teamMembers = $this->activeProject->members()
                ->where('status', 'active')
                ->get();
        } else {
            $this->isOwner = false;
            $this->teamMembers = [];
        }
    }

    // Settings & DoD Methods
    public function openSettings()
    {
        if (!$this->activeProject) return;
        $this->editName = $this->activeProject->name;
        $this->editDescription = $this->activeProject->description ?: '';
        $this->editProductGoal = $this->activeProject->product_goal ?: '';
        $this->editDefaultSprintLength = $this->activeProject->default_sprint_length_days;
        $this->editDoDList = $this->activeProject->activeDefinitionOfDone ? $this->activeProject->activeDefinitionOfDone->checklist : [];
        $this->showSettingsModal = true;
    }

    public function addEditDoDItem()
    {
        $this->newDoDItem = trim($this->newDoDItem);
        if ($this->newDoDItem !== '') {
            $this->editDoDList[] = $this->newDoDItem;
            $this->newDoDItem = '';
        }
    }

    public function removeEditDoDItem($index)
    {
        if (isset($this->editDoDList[$index])) {
            unset($this->editDoDList[$index]);
            $this->editDoDList = array_values($this->editDoDList);
        }
    }

    public function saveSettings()
    {
        if (!$this->activeProject) return;

        if ($this->user->cannot('update', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to update project settings.', type: 'danger');
            return;
        }

        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:2000',
            'editProductGoal' => 'required|string|max:5000',
            'editDefaultSprintLength' => 'required|integer|min:1|max:30',
            'editDoDList' => 'required|array|min:1',
            'editDoDList.*' => 'required|string|max:255',
        ]);

        DB::transaction(function() {
            $this->activeProject->update([
                'name' => $this->editName,
                'description' => $this->editDescription ?: null,
                'product_goal' => $this->editProductGoal,
                'default_sprint_length_days' => (int) $this->editDefaultSprintLength,
            ]);

            $dod = $this->activeProject->activeDefinitionOfDone;
            if ($dod) {
                $dod->update([
                    'checklist' => $this->editDoDList,
                ]);
            } else {
                $this->activeProject->definitionsOfDone()->create([
                    'title' => DefinitionOfDone::defaultTitle(),
                    'checklist' => $this->editDoDList,
                    'is_active' => true,
                    'created_by_user_id' => $this->user->id,
                ]);
            }
        });

        $this->showSettingsModal = false;
        $this->dispatch('toast', message: 'Project settings updated successfully.', type: 'success');
        $this->mount();
    }

    // Team Management Methods
    public function openTeam()
    {
        if (!$this->activeProject) return;
        $this->inviteEmail = '';
        $this->inviteRole = 'member';
        $this->loadInvitations();
        $this->showTeamModal = true;
    }

    public function loadInvitations()
    {
        if ($this->activeProject) {
            $this->projectInvitations = $this->activeProject->invitations()
                ->where('status', 'pending')
                ->latest()
                ->get();
        } else {
            $this->projectInvitations = [];
        }
    }

    public function sendInvitation()
    {
        if (!$this->activeProject) return;

        if ($this->user->cannot('invite', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to invite members.', type: 'danger');
            return;
        }

        $this->inviteEmail = strtolower(trim($this->inviteEmail));

        $this->validate([
            'inviteEmail' => 'required|email',
            'inviteRole' => 'required|string|in:member,supervisor',
        ]);

        if (strtolower($this->user->email) === $this->inviteEmail) {
            $this->dispatch('toast', message: 'You cannot invite yourself.', type: 'danger');
            return;
        }

        $existingUser = \App\Models\User::whereRaw('lower(email) = ?', [$this->inviteEmail])->first();
        if ($existingUser && $this->activeProject->memberships()->where('user_id', $existingUser->id)->where('status', 'active')->exists()) {
            $this->dispatch('toast', message: 'This user is already a member.', type: 'danger');
            return;
        }

        // Check if there's already a pending invitation for this email
        $existingInvite = $this->activeProject->invitations()
            ->where('email', $this->inviteEmail)
            ->where('status', 'pending')
            ->exists();

        if ($existingInvite) {
            $this->dispatch('toast', message: 'An invitation is already pending for this email.', type: 'danger');
            return;
        }

        $plainToken = \Illuminate\Support\Str::random(64);

        DB::transaction(function() use ($plainToken) {
            $this->activeProject->invitations()->create([
                'email' => $this->inviteEmail,
                'role' => $this->inviteRole,
                'token' => $plainToken,
                'token_hash' => hash('sha256', $plainToken),
                'status' => 'pending',
                'invited_by_user_id' => $this->user->id,
                'expires_at' => now()->addDays(7),
            ]);
        });

        $this->inviteEmail = '';
        $this->loadInvitations();
        $this->dispatch('toast', message: 'Invitation sent successfully.', type: 'success');
    }

    public function revokeInvitation($invitationId)
    {
        if (!$this->activeProject) return;

        if ($this->user->cannot('invite', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to revoke invitations.', type: 'danger');
            return;
        }

        $invitation = $this->activeProject->invitations()->where('id', $invitationId)->first();
        if ($invitation && $invitation->status === 'pending') {
            $invitation->update(['status' => 'revoked']);
            $this->loadInvitations();
            $this->dispatch('toast', message: 'Invitation revoked.', type: 'success');
        }
    }

    public function changeMemberRole($userId, $newRole)
    {
        if (!$this->activeProject) return;

        if ($this->user->cannot('manageMembers', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to manage members.', type: 'danger');
            return;
        }

        if (!in_array($newRole, ['member', 'supervisor'])) {
            return;
        }

        $membership = $this->activeProject->memberships()->where('user_id', $userId)->first();
        if ($membership) {
            $membership->update(['role' => $newRole]);
            $this->mount();
            $this->dispatch('toast', message: 'Member role updated successfully.', type: 'success');
        }
    }

    public function removeMember($userId)
    {
        if (!$this->activeProject) return;

        if ($this->user->cannot('manageMembers', $this->activeProject)) {
            $this->dispatch('toast', message: 'You are not authorized to manage members.', type: 'danger');
            return;
        }

        if ($this->activeProject->owner_user_id === $userId) {
            $this->dispatch('toast', message: 'You cannot remove the project owner.', type: 'danger');
            return;
        }

        $membership = $this->activeProject->memberships()->where('user_id', $userId)->first();
        if ($membership) {
            $membership->delete();
            $this->mount();
            $this->dispatch('toast', message: 'Member removed from project.', type: 'success');
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
                        <div class="flex-grow">
                            <h2 class="text-2xl font-black text-[#604B10] tracking-tight">{{ $activeProject->name }}</h2>
                            <p class="text-[#6E5003] mt-3 leading-relaxed font-medium">
                                {{ $activeProject->description ?: 'No description provided.' }}
                            </p>
                        </div>
                        @if ($isOwner)
                            <button wire:click="openSettings" class="p-2.5 rounded-full bg-[#FDCB40]/10 text-[#604B10] hover:bg-[#FDCB40]/25 transition cursor-pointer outline-none border-none shrink-0" title="Project Settings">
                                <x-heroicon-s-cog-6-tooth class="w-5 h-5"/>
                            </button>
                        @endif
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

                    @if ($activeProject->activeDefinitionOfDone && !empty($activeProject->activeDefinitionOfDone->checklist))
                        <div class="mt-6 p-4 rounded-2xl bg-[#FDCB40]/10 border border-[#FDCB40]/25">
                            <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider flex items-center gap-1.5 mb-2.5">
                                <x-heroicon-s-document-check class="w-4 h-4"/>
                                Definition of Done (DoD)
                            </h4>
                            <ul class="space-y-1.5">
                                @foreach ($activeProject->activeDefinitionOfDone->checklist as $item)
                                    <li class="flex items-start gap-2 text-sm font-semibold text-[#6E5003]">
                                        <x-heroicon-s-check class="w-5 h-5 text-green-600 shrink-0 mt-0.5"/>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
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
                    <div class="flex items-center justify-between pb-4 mb-4">
                        <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2">
                            <x-heroicon-s-users class="w-5 h-5"/>
                            Team Members
                        </h3>
                        @if ($isOwner)
                            <button wire:click="openTeam" class="text-xs font-bold text-[#604B10] bg-[#FDCB40]/20 px-3 py-1.5 rounded-full hover:bg-[#FDCB40]/40 transition border-none outline-none cursor-pointer">
                                Manage
                            </button>
                        @endif
                    </div>
                    
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
                        @if($teamMembers && !$teamMembers->isEmpty())
                            @foreach($teamMembers as $member)
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

    <!-- Project Settings Modal -->
    @if ($showSettingsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showSettingsModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Project Settings</h3>

                <form wire:submit.prevent="saveSettings" class="space-y-5">
                    <!-- Project Name -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Project Name</label>
                        <input type="text" wire:model="editName" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                        @error('editName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Product Goal -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Product Goal</label>
                        <textarea wire:model="editProductGoal" rows="3" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('editProductGoal') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Default Sprint Length -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Default Sprint Length (Days)</label>
                        <input type="number" wire:model="editDefaultSprintLength" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                        @error('editDefaultSprintLength') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Description</label>
                        <textarea wire:model="editDescription" rows="3" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('editDescription') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Definition of Done (DoD) -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Definition of Done (DoD)</label>
                        <div class="space-y-2 mb-4">
                            @foreach ($editDoDList as $index => $item)
                                <div class="flex items-center justify-between bg-[#FDCB40]/5 px-3 py-2 rounded-xl">
                                    <span class="text-sm font-semibold text-[#6E5003]">{{ $item }}</span>
                                    <button type="button" wire:click="removeEditDoDItem({{ $index }})" class="text-rose-600 hover:text-rose-800 bg-transparent border-none outline-none cursor-pointer">
                                        <x-heroicon-s-trash class="w-4 h-4"/>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="flex gap-2">
                            <input type="text" wire:model="newDoDItem" placeholder="Add criteria..." class="flex-grow bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" wire:keydown.enter.prevent="addEditDoDItem" />
                            <button type="button" wire:click="addEditDoDItem" class="bg-[#FDCB40] text-[#604B10] px-4 py-2.5 rounded-xl font-bold hover:bg-[#FDCB40]/90 transition border-none outline-none cursor-pointer flex items-center justify-center">
                                <x-heroicon-s-plus class="w-4 h-4"/>
                            </button>
                        </div>
                        @error('editDoDList') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-[#6E5003]/10">
                        <button type="button" wire:click="$set('showSettingsModal', false)" class="px-5 py-2.5 rounded-full border border-[#6E5003]/20 bg-white text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/10 transition cursor-pointer outline-none">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Team Management Modal -->
    @if ($showTeamModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-2xl w-full shadow-2xl border border-white/50 max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showTeamModal', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Manage Team</h3>

                <!-- Invite Form -->
                <div class="bg-[#FDCB40]/10 p-5 rounded-2xl border border-[#FDCB40]/20 mb-6">
                    <h4 class="text-sm font-extrabold text-[#604B10] mb-3 uppercase tracking-wider text-left">Invite New Member</h4>
                    <form wire:submit.prevent="sendInvitation" class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-grow">
                            <input type="email" wire:model="inviteEmail" placeholder="colleague@example.com" class="w-full bg-white text-[#604B10] px-4 py-2.5 rounded-xl outline-none focus:ring-1 focus:ring-[#FDCB40] font-semibold border border-[#6E5003]/10 transition-colors" />
                            @error('inviteEmail') <span class="text-xs text-red-600 mt-1 block text-left">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-full sm:w-40">
                            <select wire:model="inviteRole" class="w-full bg-white text-[#604B10] px-4 py-2.5 rounded-xl outline-none border border-[#6E5003]/10 font-semibold cursor-pointer">
                                <option value="member">Member</option>
                                <option value="supervisor">Supervisor</option>
                            </select>
                            @error('inviteRole') <span class="text-xs text-red-600 mt-1 block text-left">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="bg-[#FDCB40] text-[#604B10] px-5 py-2.5 rounded-xl font-extrabold hover:bg-[#FDCB40]/90 transition border-none outline-none cursor-pointer flex items-center justify-center gap-1.5 shrink-0">
                            <x-heroicon-s-paper-airplane class="w-4 h-4"/>
                            Invite
                        </button>
                    </form>
                </div>

                <!-- Tabs/Section: Pending Invitations & Active Members -->
                <div class="space-y-6 text-left">
                    <!-- Sent/Pending Invitations -->
                    @if (count($projectInvitations) > 0)
                        <div>
                            <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Pending Invitations</h4>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($projectInvitations as $invite)
                                    <div class="flex items-center justify-between bg-slate-50 border border-slate-100 p-3.5 rounded-xl">
                                        <div>
                                            <p class="text-sm font-bold text-[#604B10]">{{ $invite->email }}</p>
                                            <p class="text-[10px] text-slate-500 font-semibold uppercase mt-0.5">Role: {{ $invite->role }} | Expires {{ $invite->expires_at->diffForHumans() }}</p>
                                        </div>
                                        <button wire:click="revokeInvitation({{ $invite->id }})" class="px-3 py-1.5 rounded-full text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 transition cursor-pointer outline-none">
                                            Revoke
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Active Members List -->
                    <div>
                        <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Active Members</h4>
                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            @foreach ($teamMembers as $member)
                                <div class="flex items-center justify-between p-3 bg-white border border-[#6E5003]/10 rounded-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-[#FDCB40]/20 text-[#604B10] font-black flex items-center justify-center overflow-hidden">
                                            @if ($member->avatar_url)
                                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-full h-full object-cover">
                                            @else
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-extrabold text-[#604B10]">{{ $member->name }}</h5>
                                            <p class="text-xs text-[#876A1A]">{{ $member->email }}</p>
                                        </div>
                                    </div>

                                    @if ($member->id === $activeProject->owner_user_id)
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-[#604B10]/10 text-[#604B10]">
                                            Owner
                                        </span>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <!-- Role selector -->
                                            <select wire:change="changeMemberRole({{ $member->id }}, $event.target.value)" class="text-xs font-bold text-[#604B10] bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-lg cursor-pointer outline-none">
                                                <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                                <option value="supervisor" {{ $member->pivot->role === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                            </select>

                                            <!-- Remove button -->
                                            <button wire:click="removeMember({{ $member->id }})" class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition border-none cursor-pointer outline-none" title="Remove Member">
                                                <x-heroicon-s-trash class="w-4 h-4"/>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>