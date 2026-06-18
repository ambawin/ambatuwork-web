<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\ProjectInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $pendingInvitations;

    public function mount()
    {
        $this->loadInvitations();
    }

    public function loadInvitations()
    {
        $user = Auth::user();
        $this->pendingInvitations = ProjectInvitation::query()
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->with(['project.owner', 'invitedBy'])
            ->latest('id')
            ->get();
    }

    public function acceptInvitation($id)
    {
        $user = Auth::user();
        $invitation = ProjectInvitation::query()
            ->where('id', $id)
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            $this->dispatch('toast', message: 'Invitation not found.', type: 'danger');
            return;
        }

        if ($invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);
            $this->loadInvitations();
            $this->dispatch('toast', message: 'This invitation has expired.', type: 'danger');
            return;
        }

        DB::transaction(function () use ($user, $invitation): void {
            $invitation->project->memberships()->updateOrCreate(
                [
                    'project_id' => $invitation->project_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                    'status' => 'active',
                    'invited_by_user_id' => $invitation->invited_by_user_id,
                    'joined_at' => now(),
                ]
            );

            $invitation->forceFill([
                'status' => 'accepted',
                'accepted_by_user_id' => $user->id,
                'accepted_at' => now(),
            ])->save();
        });

        // Set the active project to the accepted one
        session(['active_project_id' => $invitation->project_id]);

        $this->dispatch('toast', message: 'You have joined the project ' . $invitation->project->name . '!', type: 'success');
        $this->loadInvitations();
        
        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function declineInvitation($id)
    {
        $user = Auth::user();
        $invitation = ProjectInvitation::query()
            ->where('id', $id)
            ->where('email', $user->email)
            ->where('status', 'pending')
            ->first();

        if ($invitation) {
            $invitation->update(['status' => 'declined']);
            $this->dispatch('toast', message: 'Invitation declined.', type: 'success');
            $this->loadInvitations();
        }
    }
};
?>

<div class="max-w-4xl mx-auto px-6 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-[#6E5003]">Notifications</h1>
        <p class="text-sm text-[#876A1A] mt-1">Manage your pending invitations and project updates.</p>
    </div>

    @if ($pendingInvitations->isEmpty())
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl border border-white/50 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-bell class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">No New Notifications</h3>
            <p class="text-sm text-[#876A1A] max-w-sm mx-auto">
                You are all caught up! When you receive invitations to join projects, they will appear here.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($pendingInvitations as $invite)
                <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl border border-white/50 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#FDCB40]/25 text-[#604B10] flex items-center justify-center shrink-0">
                            <x-heroicon-s-envelope-open class="w-6 h-6"/>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[#604B10] text-lg leading-snug">Project Invitation</h3>
                            <p class="text-sm text-[#6E5003] mt-1">
                                <span class="font-black">{{ $invite->invitedBy->name }}</span> has invited you to join the project <span class="font-black text-[#604B10]">{{ $invite->project->name }}</span> as a <span class="font-extrabold px-2 py-0.5 bg-[#FDCB40]/30 rounded-lg text-xs">{{ ucfirst($invite->role) }}</span>.
                            </p>
                            <p class="text-xs text-slate-500 font-semibold mt-2">
                                Invited {{ $invite->created_at->diffForHumans() }} (Expires {{ $invite->expires_at->format('M d, Y') }})
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 shrink-0 self-end md:self-center">
                        <button wire:click="declineInvitation({{ $invite->id }})" class="px-5 py-2.5 rounded-full border border-rose-200 text-rose-600 hover:bg-rose-50 text-sm font-extrabold transition cursor-pointer outline-none bg-white">
                            Decline
                        </button>
                        <button wire:click="acceptInvitation({{ $invite->id }})" class="px-5 py-2.5 rounded-full bg-green-600 hover:bg-green-700 text-white text-sm font-extrabold transition cursor-pointer border-none outline-none">
                            Accept & Join
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
