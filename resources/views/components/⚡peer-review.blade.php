<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $project;
    public $sprint;
    public $cycle;
    public $isOwner = false;
    public $isSupervisor = false;
    public $membersToReview = [];
    public $reviewCycleStatus = 'closed';
    public $isCycleActive = false;
    
    // Form variables
    public $selectedMemberId;
    public $selectedMemberName = '';
    public $collaborationScore = 5;
    public $deliveryScore = 5;
    public $communicationScore = 5;
    public $continueFeedback = '';
    public $improveFeedback = '';
    public $showReviewForm = false;

    // Summaries
    public $cycleSummary = [];
    public $mySummary = [];
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
        $this->isSupervisor = $project->roleFor($user) === 'supervisor';

        $this->loadCycleDetails();
    }

    public function loadCycleDetails()
    {
        $user = Auth::user();
        $this->sprint->refresh();
        $this->cycle = $this->sprint->peerReviewCycle;
        
        if ($this->cycle) {
            $this->reviewCycleStatus = $this->cycle->status;
            $this->isCycleActive = $this->cycle->status === 'open';

            $this->membersToReview = $this->project->members()
                ->where('status', 'active')
                ->wherePivot('role', '!=', 'supervisor')
                ->where('users.id', '!=', $user->id)
                ->get();

            $reviewedUserIds = $this->cycle->reviews()
                ->where('reviewer_user_id', $user->id)
                ->pluck('reviewee_user_id')
                ->toArray();

            foreach ($this->membersToReview as $member) {
                $member->already_reviewed = in_array($member->id, $reviewedUserIds);
            }

            if ($this->isOwner) {
                $this->loadOwnerSummary();
            }

            $this->loadMySummary();
        } else {
            $this->reviewCycleStatus = 'none';
            $this->isCycleActive = false;
            $this->membersToReview = [];
            $this->cycleSummary = [];
            $this->mySummary = [];
        }
    }

    public function startCycle()
    {
        if (!$this->isOwner) return;

        $this->cycle = \App\Models\PeerReviewCycle::firstOrCreate(
            ['sprint_id' => $this->sprint->id],
            [
                'project_id' => $this->project->id,
                'status' => 'open',
                'opens_at' => now(),
            ]
        );

        $this->dispatch('toast', message: 'Peer Review Cycle opened successfully.', type: 'success');
        $this->loadCycleDetails();
    }

    public function closeCycle()
    {
        if (!$this->isOwner) return;
        if (!$this->cycle) return;

        $this->cycle->update([
            'status' => 'closed',
            'closes_at' => now(),
        ]);

        $this->dispatch('toast', message: 'Peer Review Cycle closed successfully.', type: 'success');
        $this->loadCycleDetails();
    }

    public function openReviewForm($memberId, $memberName)
    {
        if ($this->isSupervisor) {
            $this->dispatch('toast', message: 'Supervisors cannot fill peer reviews.', type: 'danger');
            return;
        }

        $this->selectedMemberId = $memberId;
        $this->selectedMemberName = $memberName;
        
        $user = Auth::user();
        $existing = $this->cycle->reviews()
            ->where('reviewer_user_id', $user->id)
            ->where('reviewee_user_id', $memberId)
            ->first();

        if ($existing) {
            $this->collaborationScore = $existing->collaboration_score;
            $this->deliveryScore = $existing->delivery_score;
            $this->communicationScore = $existing->communication_score;
            $this->continueFeedback = $existing->continue_feedback ?: '';
            $this->improveFeedback = $existing->improve_feedback ?: '';
        } else {
            $this->collaborationScore = 5;
            $this->deliveryScore = 5;
            $this->communicationScore = 5;
            $this->continueFeedback = '';
            $this->improveFeedback = '';
        }

        $this->showReviewForm = true;
    }

    public function submitReview()
    {
        if ($this->isSupervisor) return;

        $this->validate([
            'collaborationScore' => 'required|integer|min:1|max:5',
            'deliveryScore' => 'required|integer|min:1|max:5',
            'communicationScore' => 'required|integer|min:1|max:5',
            'continueFeedback' => 'nullable|string|max:1000',
            'improveFeedback' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        \App\Models\PeerReview::updateOrCreate(
            [
                'peer_review_cycle_id' => $this->cycle->id,
                'reviewer_user_id' => $user->id,
                'reviewee_user_id' => $this->selectedMemberId,
            ],
            [
                'collaboration_score' => (int) $this->collaborationScore,
                'delivery_score' => (int) $this->deliveryScore,
                'communication_score' => (int) $this->communicationScore,
                'continue_feedback' => $this->continueFeedback ?: null,
                'improve_feedback' => $this->improveFeedback ?: null,
                'submitted_at' => now(),
            ]
        );

        $this->showReviewForm = false;
        $this->dispatch('toast', message: 'Review submitted successfully.', type: 'success');
        $this->loadCycleDetails();
    }

    public function loadOwnerSummary()
    {
        $activeMembers = $this->project->members()
            ->wherePivot('role', '!=', 'supervisor')
            ->get();

        $this->cycleSummary = [];
        foreach ($activeMembers as $member) {
            $reviews = $this->cycle->reviews()->where('reviewee_user_id', $member->id)->get();
            $count = $reviews->count();
            $avgCollab = $count > 0 ? round($reviews->avg('collaboration_score'), 2) : null;
            $avgDelivery = $count > 0 ? round($reviews->avg('delivery_score'), 2) : null;
            $avgComm = $count > 0 ? round($reviews->avg('communication_score'), 2) : null;

            $feedbacks = $reviews->map(function ($r) {
                return [
                    'continue' => $r->continue_feedback,
                    'improve' => $r->improve_feedback,
                ];
            })->filter(fn($f) => !empty($f['continue']) || !empty($f['improve']))->values()->toArray();

            $this->cycleSummary[] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar_url' => $member->avatar_url,
                ],
                'review_count' => $count,
                'avg_collaboration_score' => $avgCollab,
                'avg_delivery_score' => $avgDelivery,
                'avg_communication_score' => $avgComm,
                'feedbacks' => $feedbacks,
            ];
        }
    }

    public function loadMySummary()
    {
        $user = Auth::user();
        $reviews = $this->cycle->reviews()->where('reviewee_user_id', $user->id)->get();
        $count = $reviews->count();
        $avgCollab = $count > 0 ? round($reviews->avg('collaboration_score'), 2) : null;
        $avgDelivery = $count > 0 ? round($reviews->avg('delivery_score'), 2) : null;
        $avgComm = $count > 0 ? round($reviews->avg('communication_score'), 2) : null;

        $feedbacks = $reviews->map(function ($r) {
            return [
                'continue' => $r->continue_feedback,
                'improve' => $r->improve_feedback,
            ];
        })->filter(fn($f) => !empty($f['continue']) || !empty($f['improve']))->values()->toArray();

        $this->mySummary = [
            'review_count' => $count,
            'avg_collaboration_score' => $avgCollab,
            'avg_delivery_score' => $avgDelivery,
            'avg_communication_score' => $avgComm,
            'feedbacks' => $feedbacks,
        ];
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
            <h1 class="text-3xl font-extrabold text-[#6E5003]">Peer Review Cycle</h1>
            <p class="text-sm text-[#876A1A] mt-1">
                Evaluate team performance and collaboration for <span class="font-extrabold text-[#604B10]">{{ $sprint->name }}</span>.
            </p>
        </div>
    </div>

    <!-- Cycle Control Section -->
    <div class="bg-white/85 backdrop-blur-md p-6 rounded-3xl mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6 text-left shadow-sm">
        <div>
            <h3 class="font-extrabold text-lg text-[#604B10] flex items-center gap-1.5">
                <x-heroicon-s-user-group class="w-5 h-5 text-[#604B10]"/>
                Siklus Peer Review
            </h3>
            <p class="text-xs text-[#876A1A] mt-1">
                @if ($reviewCycleStatus === 'none')
                    The peer review cycle has not been started for this sprint.
                @elseif ($reviewCycleStatus === 'open')
                    The peer review cycle is currently OPEN. Members can submit reviews.
                @else
                    The peer review cycle is CLOSED. Aggregate anonymous scores are visible.
                @endif
            </p>
        </div>
        <div class="shrink-0 flex gap-3">
            @if ($isOwner)
                @if ($reviewCycleStatus === 'none')
                    <button wire:click="startCycle" class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                        Start Cycle
                    </button>
                @elseif ($reviewCycleStatus === 'open')
                    <button wire:click="closeCycle" class="px-5 py-2.5 rounded-full bg-[#604B10] text-white text-sm font-extrabold hover:bg-[#604B10]/90 transition cursor-pointer border-none outline-none">
                        Close Cycle
                    </button>
                @endif
            @endif
        </div>
    </div>

    @if ($reviewCycleStatus === 'none')
        <div class="bg-white/85 backdrop-blur-md p-12 rounded-3xl text-center space-y-4 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FDCB40]/20 flex items-center justify-center mx-auto text-[#604B10]">
                <x-heroicon-s-lock-closed class="w-8 h-8"/>
            </div>
            <h3 class="text-xl font-black text-[#604B10]">Cycle Not Started</h3>
            <p class="text-sm text-[#876A1A] max-w-md mx-auto">
                Ask the Product Owner to start the peer review cycle for this sprint. Teammates can only review each other once the cycle is opened.
            </p>
        </div>
    @else
        <!-- Grid layout: Review Form List (Left/Main) & Owner/Self Summary (Right or Full) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Side: List of teammates to review (only if cycle is active) -->
            <div class="lg:col-span-2 space-y-6">
                @if ($isCycleActive && !$isSupervisor)
                    <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl text-left shadow-sm">
                        <h3 class="text-lg font-black text-[#604B10] mb-4">Teammate Evaluations</h3>
                        <div class="space-y-4">
                            @foreach ($membersToReview as $member)
                                <div class="flex items-center justify-between p-4 bg-[#604B10]/5 rounded-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-[#FDCB40]/20 text-[#604B10] font-black flex items-center justify-center overflow-hidden">
                                            @if ($member->avatar_url)
                                                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-full h-full object-cover">
                                            @else
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-extrabold text-[#604B10]">{{ $member->name }}</h4>
                                            <p class="text-xs text-[#876A1A]">{{ ucfirst($member->pivot->role ?? 'Member') }}</p>
                                        </div>
                                    </div>

                                    <button wire:click="openReviewForm({{ $member->id }}, '{{ $member->name }}')" class="px-4 py-2 rounded-full text-xs font-extrabold transition cursor-pointer border-none outline-none {{ $member->already_reviewed ? 'bg-[#604B10]/10 text-[#604B10] hover:bg-[#604B10]/20' : 'bg-[#FDCB40] text-[#604B10] hover:bg-[#FDCB40]/90' }}">
                                        {{ $member->already_reviewed ? 'Edit Review' : 'Start Review' }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif ($isCycleActive && $isSupervisor)
                    <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl text-center shadow-sm py-12">
                        <x-heroicon-s-eye class="w-10 h-10 mx-auto text-[#876A1A]/50 mb-2"/>
                        <h4 class="font-extrabold text-[#604B10]">Observing Mode</h4>
                        <p class="text-xs text-[#876A1A]/80 max-w-xs mx-auto mt-1">Supervisors observe team reviews but do not participate in grading.</p>
                    </div>
                @else
                    <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl text-center shadow-sm py-12">
                        <x-heroicon-s-lock-closed class="w-10 h-10 mx-auto text-[#876A1A]/50 mb-2"/>
                        <h4 class="font-extrabold text-[#604B10]">Cycle is Closed</h4>
                        <p class="text-xs text-[#876A1A]/80 max-w-xs mx-auto mt-1">Evaluations are locked. Check your feedback summaries on the side.</p>
                    </div>
                @endif
            </div>

            <!-- Right Side: My Summary & Owner aggregate dashboards -->
            <div class="space-y-6 text-left">
                <!-- My anonymous feedback summary -->
                @if ($reviewCycleStatus === 'closed')
                    <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-sm">
                        <h3 class="text-lg font-black text-[#604B10] mb-4 flex items-center gap-1.5">
                            <x-heroicon-s-chat-bubble-left-ellipsis class="w-5 h-5 text-[#604B10]"/>
                            My Feedback Summary
                        </h3>

                        @if ($mySummary['review_count'] === 0)
                            <p class="text-xs text-[#876A1A]/85 italic">No feedback received for this sprint.</p>
                        @else
                            <div class="space-y-4">
                                <div class="grid grid-cols-3 gap-2 bg-[#FDCB40]/10 p-3 rounded-2xl text-center">
                                    <div>
                                        <span class="text-xs text-[#876A1A] font-bold block leading-none">Collab</span>
                                        <span class="text-lg font-black text-[#604B10] mt-1 block">{{ $mySummary['avg_collaboration_score'] ?: '-' }}/5</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-[#876A1A] font-bold block leading-none">Delivery</span>
                                        <span class="text-lg font-black text-[#604B10] mt-1 block">{{ $mySummary['avg_delivery_score'] ?: '-' }}/5</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-[#876A1A] font-bold block leading-none">Comm</span>
                                        <span class="text-lg font-black text-[#604B10] mt-1 block">{{ $mySummary['avg_communication_score'] ?: '-' }}/5</span>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-2">Teammate Written Feedback</h4>
                                    <div class="space-y-3 max-h-60 overflow-y-auto">
                                        @foreach ($mySummary['feedbacks'] as $fb)
                                            <div class="bg-[#604B10]/5 p-3 rounded-xl text-xs space-y-1.5">
                                                @if ($fb['continue'])
                                                    <p><strong class="text-[#604B10] font-extrabold">Continue:</strong> {{ $fb['continue'] }}</p>
                                                @endif
                                                @if ($fb['improve'])
                                                    <p><strong class="text-[#876A1A] font-extrabold">Improve:</strong> {{ $fb['improve'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Owner aggregate dashboard -->
                @if ($isOwner)
                    <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl shadow-sm">
                        <h3 class="text-lg font-black text-[#604B10] mb-4 flex items-center gap-1.5">
                            <x-heroicon-s-presentation-chart-line class="w-5 h-5 text-[#604B10]"/>
                            Aggregate Summary (PO)
                        </h3>

                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            @foreach ($cycleSummary as $memberSummary)
                                <div class="p-4 bg-[#604B10]/5 rounded-2xl text-xs space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-sm text-[#604B10]">{{ $memberSummary['user']['name'] }}</span>
                                        <span class="text-[10px] text-[#876A1A]/80 font-bold">Reviews: {{ $memberSummary['review_count'] }}</span>
                                    </div>
                                    
                                    @if ($memberSummary['review_count'] > 0)
                                        <div class="grid grid-cols-3 gap-2 text-center bg-white p-2 rounded-xl">
                                            <div>
                                                <span class="text-[9px] text-[#876A1A] font-bold block">Collab</span>
                                                <span class="font-extrabold text-[#604B10]">{{ $memberSummary['avg_collaboration_score'] }}/5</span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] text-[#876A1A] font-bold block">Delivery</span>
                                                <span class="font-extrabold text-[#604B10]">{{ $memberSummary['avg_delivery_score'] }}/5</span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] text-[#876A1A] font-bold block">Comm</span>
                                                <span class="font-extrabold text-[#604B10]">{{ $memberSummary['avg_communication_score'] }}/5</span>
                                            </div>
                                        </div>

                                        @if (count($memberSummary['feedbacks']) > 0)
                                            <div class="space-y-2 pt-2">
                                                @foreach ($memberSummary['feedbacks'] as $fb)
                                                    <div class="space-y-1 bg-white/50 p-1.5 rounded-lg">
                                                        @if ($fb['continue'])
                                                            <p><strong class="text-[#604B10] font-extrabold">Cont:</strong> {{ $fb['continue'] }}</p>
                                                        @endif
                                                        @if ($fb['improve'])
                                                            <p><strong class="text-[#876A1A] font-extrabold">Imp:</strong> {{ $fb['improve'] }}</p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-[10px] text-[#876A1A]/80 italic">No reviews completed yet.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

        </div>
    @endif

    <!-- Review Submission Form Modal -->
    @if ($showReviewForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl max-w-xl w-full shadow-[0_25px_60px_-15px_rgba(96,75,16,0.25)] max-h-[90vh] overflow-y-auto relative text-[#6E5003]">
                <!-- Close Button -->
                <button wire:click="$set('showReviewForm', false)" class="absolute top-6 right-6 text-[#6E5003] hover:text-[#604B10] bg-transparent border-none outline-none cursor-pointer">
                    <x-heroicon-s-x-mark class="w-6 h-6"/>
                </button>

                <h3 class="text-2xl font-black text-[#604B10] mb-6">Review for {{ $selectedMemberName }}</h3>

                <form wire:submit.prevent="submitReview" class="space-y-5 text-left">
                    <!-- Collaboration -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider">Collaboration Score (1-5)</label>
                            <span class="text-xs font-black text-[#604B10] bg-[#FDCB40]/20 px-2 py-0.5 rounded">{{ $collaborationScore }}/5</span>
                        </div>
                        <input type="range" wire:model="collaborationScore" min="1" max="5" class="w-full accent-[#604B10]" />
                    </div>

                    <!-- Delivery -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider">Delivery Score (1-5)</label>
                            <span class="text-xs font-black text-[#604B10] bg-[#FDCB40]/20 px-2 py-0.5 rounded">{{ $deliveryScore }}/5</span>
                        </div>
                        <input type="range" wire:model="deliveryScore" min="1" max="5" class="w-full accent-[#604B10]" />
                    </div>

                    <!-- Communication -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider">Communication Score (1-5)</label>
                            <span class="text-xs font-black text-[#604B10] bg-[#FDCB40]/20 px-2 py-0.5 rounded">{{ $communicationScore }}/5</span>
                        </div>
                        <input type="range" wire:model="communicationScore" min="1" max="5" class="w-full accent-[#604B10]" />
                    </div>

                    <!-- Continue Feedback -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What should {{ $selectedMemberName }} CONTINUE doing?</label>
                        <textarea wire:model="continueFeedback" rows="3" placeholder="e.g. Great code quality, very helpful during blockers." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('continueFeedback') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Improve Feedback -->
                    <div>
                        <label class="block text-xs font-bold text-[#6E5003] uppercase tracking-wider mb-2">What could {{ $selectedMemberName }} IMPROVE on?</label>
                        <textarea wire:model="improveFeedback" rows="3" placeholder="e.g. Update task card statuses more frequently." class="w-full bg-[#FDCB40]/10 text-[#604B10] px-4 py-3 rounded-2xl outline-none focus:bg-[#FDCB40]/20 font-semibold border-none transition-colors resize-none"></textarea>
                        @error('improveFeedback') <span class="text-xs text-[#604B10] font-extrabold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showReviewForm', false)" class="px-5 py-2.5 rounded-full bg-[#604B10]/10 text-[#604B10] text-sm font-extrabold hover:bg-[#604B10]/20 transition cursor-pointer outline-none border-none">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#FDCB40] text-[#604B10] text-sm font-extrabold hover:bg-[#FDCB40]/90 transition cursor-pointer border-none outline-none">
                            Submit Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
