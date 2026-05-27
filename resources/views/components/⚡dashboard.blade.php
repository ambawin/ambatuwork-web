<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\ProjectInvitation;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $user;
    public $ownedProjects;
    public $joinedProjects;
    public $pendingInvitations;
    public $totalProjectsCount;
    public $activeSprintsCount;

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
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-[#6E5003]">Welcome back, {{ $user->name }}!</h1>
        <p class="text-sm text-[#876A1A] mt-1">Keep track of your sprints and team assignments.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Projects -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-[#6E5003]/10">
            <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Total Projects</h3>
            <p class="text-4xl font-extrabold text-[#604B10] mt-2">{{ $totalProjectsCount }}</p>
        </div>
        
        <!-- Active Sprints -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-[#6E5003]/10">
            <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Active Sprints</h3>
            <p class="text-4xl font-extrabold text-[#604B10] mt-2">{{ $activeSprintsCount }}</p>
        </div>
        
        <!-- Pending Invitations -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-[#6E5003]/10">
            <h3 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider">Pending Invites</h3>
            <p class="text-4xl font-extrabold text-[#604B10] mt-2">{{ $pendingInvitations->count() }}</p>
        </div>
    </div>

    <div class="min-h-screen">

    </div>

    <div class="min-h-screen">

    </div>

    <!-- Project lists or details can go here -->
</div>