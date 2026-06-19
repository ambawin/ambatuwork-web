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
    public $activeProject;
    public $isOwner = false;

    // Project Settings & DoD properties
    public $editName = '';
    public $editDescription = '';
    public $editProductGoal = '';
    public $editDefaultSprintLength = 14;
    public $editDoDList = [];
    public $newDoDItem = '';

    // Team Management properties
    public $inviteEmail = '';
    public $inviteRole = 'member';
    public $projectInvitations = [];
    public $teamMembers = [];

    public function mount()
    {
        $this->user = Auth::user();

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
            
            // Populate project edit details
            $this->editName = $this->activeProject->name;
            $this->editDescription = $this->activeProject->description ?: '';
            $this->editProductGoal = $this->activeProject->product_goal ?: '';
            $this->editDefaultSprintLength = $this->activeProject->default_sprint_length_days;
            $this->editDoDList = $this->activeProject->activeDefinitionOfDone ? $this->activeProject->activeDefinitionOfDone->checklist : [];

            // Populate members & invitations
            $this->teamMembers = $this->activeProject->members()
                ->where('status', 'active')
                ->get();
            $this->loadInvitations();
        } else {
            $this->isOwner = false;
            $this->teamMembers = [];
            $this->projectInvitations = [];
        }
    }

    public function addEditDoDItem()
    {
        if (!$this->isOwner) return;
        $this->newDoDItem = trim($this->newDoDItem);
        if ($this->newDoDItem !== '') {
            $this->editDoDList[] = $this->newDoDItem;
            $this->newDoDItem = '';
        }
    }

    public function removeEditDoDItem($index)
    {
        if (!$this->isOwner) return;
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

        $this->dispatch('toast', message: 'Project settings updated successfully.', type: 'success');
        $this->mount();
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
        <h1 class="text-4xl font-extrabold text-[#604B10] tracking-tight">Project Settings</h1>
        <p class="text-sm text-[#876A1A] mt-1">Configure project metadata, definition of done, and manage your team assignments.</p>
    </div>

    @if ($activeProject)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Side: Project details & Definition of Done form -->
            <div class="space-y-8">
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-sm">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6">
                        <x-heroicon-s-cog-6-tooth class="w-5 h-5 text-[#876A1A]"/>
                        Project Details
                    </h3>

                    <form wire:submit.prevent="saveSettings" class="space-y-5">
                        <!-- Project Name -->
                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Project Name</label>
                            <input type="text" wire:model="editName" @disabled(!$isOwner) class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors disabled:opacity-60" />
                            @error('editName') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Product Goal -->
                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Product Goal</label>
                            <textarea wire:model="editProductGoal" @disabled(!$isOwner) rows="3" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none disabled:opacity-60"></textarea>
                            @error('editProductGoal') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Default Sprint Length -->
                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Default Sprint Length (Days)</label>
                            <input type="number" wire:model="editDefaultSprintLength" @disabled(!$isOwner) class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors disabled:opacity-60" />
                            @error('editDefaultSprintLength') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">Description</label>
                            <textarea wire:model="editDescription" @disabled(!$isOwner) rows="3" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none disabled:opacity-60"></textarea>
                            @error('editDescription') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Definition of Done (DoD) -->
                        <div class="pt-4">
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-3">Definition of Done (DoD) Checklist</label>
                            
                            <div class="space-y-2 mb-4">
                                @foreach ($editDoDList as $index => $item)
                                    <div class="flex items-center justify-between bg-[#FDCB40]/5 px-3 py-2 rounded-xl">
                                        <span class="text-sm font-semibold text-[#6E5003]">{{ $item }}</span>
                                        @if ($isOwner)
                                            <button type="button" wire:click="removeEditDoDItem({{ $index }})" class="text-[#604B10] hover:text-[#876A1A] bg-transparent border-none outline-none cursor-pointer">
                                                <x-heroicon-s-trash class="w-4 h-4"/>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            
                            @if ($isOwner)
                                <div class="flex gap-2">
                                    <input type="text" wire:model="newDoDItem" placeholder="Add criteria..." class="flex-grow bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" wire:keydown.enter.prevent="addEditDoDItem" />
                                    <button type="button" wire:click="addEditDoDItem" class="bg-[#FDCB40] text-[#604B10] px-4 py-2.5 rounded-xl font-bold hover:bg-[#FDCB40]/90 transition border-none outline-none cursor-pointer flex items-center justify-center">
                                        <x-heroicon-s-plus class="w-4 h-4"/>
                                    </button>
                                </div>
                            @endif
                            @error('editDoDList') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        @if ($isOwner)
                            <div class="pt-4 flex justify-end">
                                <button type="submit" class="px-6 py-3 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 hover:shadow-sm transition cursor-pointer border-none outline-none">
                                    Save Project Settings
                                </button>
                            </div>
                        @else
                            <p class="text-xs text-[#876A1A]/70 italic mt-4">Only the project owner can edit project settings.</p>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Right Side: Team members list & Invite Form -->
            <div class="space-y-8">
                <!-- Team Members Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-sm">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6">
                        <x-heroicon-s-users class="w-5 h-5 text-[#876A1A]"/>
                        Team Members
                    </h3>

                    <!-- Invite Form (Owner Only) -->
                    @if ($isOwner)
                        <div class="bg-[#FDCB40]/10 p-5 rounded-2xl mb-6">
                            <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Invite New Member</h4>
                            <form wire:submit.prevent="sendInvitation" class="flex flex-col gap-3">
                                <div class="w-full">
                                    <input type="email" wire:model="inviteEmail" placeholder="colleague@example.com" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors" />
                                    @error('inviteEmail') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-full">
                                    <select wire:model="inviteRole" class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-2.5 rounded-xl outline-none border-none focus:bg-[#FDCB40]/20 font-semibold cursor-pointer">
                                        <option value="member">Member</option>
                                        <option value="supervisor">Supervisor</option>
                                    </select>
                                    @error('inviteRole') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" class="w-full bg-[#FDCB40] text-[#604B10] px-5 py-2.5 rounded-xl font-extrabold hover:bg-[#FDCB40]/90 transition border-none outline-none cursor-pointer flex items-center justify-center gap-1.5">
                                    <x-heroicon-s-paper-airplane class="w-4 h-4"/>
                                    Invite
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Sent/Pending Invitations -->
                    @if ($isOwner && count($projectInvitations) > 0)
                        <div class="mb-6">
                            <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Pending Invitations</h4>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($projectInvitations as $invite)
                                    <div class="flex items-center justify-between bg-[#604B10]/5 p-3 rounded-xl">
                                        <div>
                                            <p class="text-sm font-bold text-[#604B10]">{{ $invite->email }}</p>
                                            <p class="text-[10px] text-[#876A1A]/80 font-semibold uppercase mt-0.5">Role: {{ $invite->role }} | Expires {{ $invite->expires_at->diffForHumans() }}</p>
                                        </div>
                                        <button wire:click="revokeInvitation({{ $invite->id }})" class="px-3 py-1.5 rounded-full text-xs font-extrabold bg-[#604B10]/10 text-[#604B10] hover:bg-[#604B10]/20 transition cursor-pointer border-none outline-none">
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
                        <div class="space-y-3 max-h-80 overflow-y-auto">
                            @foreach ($teamMembers as $member)
                                <div class="flex items-center justify-between p-3 bg-[#604B10]/5 rounded-2xl">
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
                                            @if ($isOwner)
                                                <!-- Role selector -->
                                                <select wire:change="changeMemberRole({{ $member->id }}, $event.target.value)" class="text-xs font-bold text-[#604B10] bg-[#FDCB40]/10 border-none px-2.5 py-1.5 rounded-lg cursor-pointer outline-none focus:bg-[#FDCB40]/20">
                                                    <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                                    <option value="supervisor" {{ $member->pivot->role === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                                </select>

                                                <!-- Remove button -->
                                                <button wire:click="removeMember({{ $member->id }})" class="p-2 bg-[#604B10]/10 text-[#604B10] rounded-lg hover:bg-[#604B10]/20 transition border-none cursor-pointer outline-none" title="Remove Member">
                                                    <x-heroicon-s-trash class="w-4 h-4"/>
                                                </button>
                                            @else
                                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-[#6E5003]/5 text-[#6E5003] capitalize">
                                                    {{ $member->pivot->role ?? 'Member' }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- No Project Selected -->
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl text-center space-y-4 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-folder-open class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No Project Selected</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                You don't have any projects yet or haven't selected one. Create a project to start configuring settings!
            </p>
        </div>
    @endif
</div>
