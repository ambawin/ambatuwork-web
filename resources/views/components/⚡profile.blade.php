<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\BacklogItem;

new #[Layout('layouts.dashboard')] class extends Component
{
    public $user;
    public $totalActiveProjects;
    
    // Backlog stats
    public $assignedTotal;
    public $completedPoints;
    public $assignedByStatus = [];

    // Checkins stats
    public $totalCheckins;
    public $averageConfidence;

    // Impediments stats
    public $reportedTotal;
    public $reportedResolved;
    public $reportedByStatus = [];

    // Peer Reviews stats
    public $submittedTotal;
    public $receivedTotal;
    public $receivedAverageScores = [];

    public function mount()
    {
        $this->user = Auth::user();

        // 1. Projects
        $this->totalActiveProjects = $this->user->projects()->count();

        // 2. Backlog Items
        $assignedBacklogItemsQuery = BacklogItem::query()
            ->where('assigned_to_user_id', $this->user->id)
            ->where('status', '!=', 'archived');
        
        $this->assignedTotal = (clone $assignedBacklogItemsQuery)->count();
        
        $this->assignedByStatus = (clone $assignedBacklogItemsQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->completedPoints = (clone $assignedBacklogItemsQuery)
            ->where('status', 'done')
            ->sum('estimate_points');

        // 3. Daily Check-ins
        $this->totalCheckins = $this->user->dailyCheckins()->count();
        $this->averageConfidence = $this->user->dailyCheckins()->avg('confidence_score') ?? 0.0;
        $this->averageConfidence = round($this->averageConfidence, 2);

        // 4. Impediments
        $reportedImpedimentsQuery = $this->user->reportedImpediments();
        $this->reportedTotal = (clone $reportedImpedimentsQuery)->count();
        $this->reportedResolved = (clone $reportedImpedimentsQuery)->where('status', 'resolved')->count();
        
        $this->reportedByStatus = (clone $reportedImpedimentsQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 5. Peer Reviews
        $this->submittedTotal = $this->user->submittedPeerReviews()->count();
        $this->receivedTotal = $this->user->receivedPeerReviews()->count();
        
        $receivedReviewsQuery = $this->user->receivedPeerReviews();
        $this->receivedAverageScores = [
            'collaboration' => round((clone $receivedReviewsQuery)->avg('collaboration_score') ?? 0.0, 1),
            'delivery' => round((clone $receivedReviewsQuery)->avg('delivery_score') ?? 0.0, 1),
            'communication' => round((clone $receivedReviewsQuery)->avg('communication_score') ?? 0.0, 1),
        ];
    }
};
?>

<div class="max-w-6xl mx-auto px-6 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-extrabold text-[#604B10] tracking-tight">Profile Details</h1>
        <p class="text-sm text-[#876A1A] mt-1">Manage your account information and view your activity statistics.</p>
    </div>

    <div class="space-y-8">
        <!-- User Info Header Card -->
        <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50 shadow-sm flex flex-col md:flex-row items-center gap-6">
            <div class="w-24 h-24 rounded-full bg-[#FDCB40]/30 text-[#604B10] font-black text-3xl flex items-center justify-center overflow-hidden shrink-0 border-4 border-white shadow-sm">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                @endif
            </div>
            <div class="text-center md:text-left flex-grow">
                <h2 class="text-3xl font-black text-[#604B10] tracking-tight">{{ $user->name }}</h2>
                <p class="text-[#876A1A] font-semibold mt-1">{{ $user->email }}</p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 mt-3 text-xs text-[#876A1A]/70 font-bold">
                    <span class="flex items-center gap-1.5 bg-[#FDCB40]/10 px-3 py-1.5 rounded-full">
                        <x-heroicon-s-calendar class="w-4 h-4 text-amber-700"/>
                        Joined {{ $user->created_at->format('M d, Y') }}
                    </span>
                    @if($user->last_login_at)
                        <span class="flex items-center gap-1.5 bg-[#FDCB40]/10 px-3 py-1.5 rounded-full">
                            <x-heroicon-s-clock class="w-4 h-4 text-amber-700"/>
                            Last Login: {{ $user->last_login_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Grid: Overview & Backlog Tasks -->
            <div class="space-y-8">
                <!-- Overview Stats Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50 shadow-sm">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6 border-b border-[#6E5003]/10 pb-4">
                        <x-heroicon-s-presentation-chart-line class="w-5 h-5 text-amber-600"/>
                        Activity Overview
                    </h3>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-2xl font-black text-[#604B10]">{{ $totalActiveProjects }}</p>
                            <p class="text-[10px] uppercase tracking-wider font-extrabold text-[#876A1A] mt-1">Active Projects</p>
                        </div>
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-2xl font-black text-[#604B10]">{{ $totalCheckins }}</p>
                            <p class="text-[10px] uppercase tracking-wider font-extrabold text-[#876A1A] mt-1">Check-ins</p>
                        </div>
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-2xl font-black text-[#604B10]">{{ $averageConfidence }}</p>
                            <p class="text-[10px] uppercase tracking-wider font-extrabold text-[#876A1A] mt-1">Avg Confidence</p>
                        </div>
                    </div>
                </div>

                <!-- Tasks & Delivery Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50 shadow-sm">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6 border-b border-[#6E5003]/10 pb-4">
                        <x-heroicon-s-clipboard-document-check class="w-5 h-5 text-amber-600"/>
                        Task & Delivery Metrics
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-center mb-6">
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-3xl font-black text-[#604B10]">{{ $assignedTotal }}</p>
                            <p class="text-xs font-bold text-[#876A1A] mt-1">Assigned Tasks</p>
                        </div>
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-3xl font-black text-[#604B10]">{{ $completedPoints }}</p>
                            <p class="text-xs font-bold text-[#876A1A] mt-1">Completed Story Points</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Task Status Breakdown</h4>
                        <div class="space-y-3">
                            @php
                                $statuses = [
                                    'todo' => ['label' => 'To Do', 'color' => 'bg-slate-400'],
                                    'in_progress' => ['label' => 'In Progress', 'color' => 'bg-blue-500'],
                                    'test' => ['label' => 'Testing', 'color' => 'bg-purple-500'],
                                    'done' => ['label' => 'Done', 'color' => 'bg-green-600'],
                                ];
                            @endphp
                            @foreach($statuses as $statusKey => $statusData)
                                @php
                                    $count = $assignedByStatus[$statusKey] ?? 0;
                                    $percent = $assignedTotal > 0 ? round(($count / $assignedTotal) * 100) : 0;
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs font-bold text-[#6E5003]">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full {{ $statusData['color'] }}"></span>
                                            {{ $statusData['label'] }}
                                        </span>
                                        <span>{{ $count }} task{{ $count != 1 ? 's' : '' }} ({{ $percent }}%)</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="h-full {{ $statusData['color'] }} rounded-full" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Grid: Impediments & Peer Evaluations -->
            <div class="space-y-8">
                <!-- Impediments Stats Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50 shadow-sm">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6 border-b border-[#6E5003]/10 pb-4">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-600"/>
                        Impediments & Blockers
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-center mb-6">
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-3xl font-black text-[#604B10]">{{ $reportedTotal }}</p>
                            <p class="text-xs font-bold text-[#876A1A] mt-1">Total Reported</p>
                        </div>
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-3xl font-black text-[#604B10]">{{ $reportedResolved }}</p>
                            <p class="text-xs font-bold text-[#876A1A] mt-1">Resolved Blockers</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Impediments Status</h4>
                        <div class="space-y-3">
                            @php
                                $openImpediments = $reportedTotal - $reportedResolved;
                                $resolvedPercent = $reportedTotal > 0 ? round(($reportedResolved / $reportedTotal) * 100) : 0;
                                $openPercent = $reportedTotal > 0 ? 100 - $resolvedPercent : 0;
                            @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-[#6E5003]">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-600"></span>
                                        Resolved
                                    </span>
                                    <span>{{ $reportedResolved }} ({{ $resolvedPercent }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-600 rounded-full" style="width: {{ $resolvedPercent }}%"></div>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-[#6E5003]">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        Unresolved / Open
                                    </span>
                                    <span>{{ $openImpediments }} ({{ $openPercent }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="h-full bg-rose-500 rounded-full" style="width: {{ $openPercent }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peer Review Performance Card -->
                <div class="bg-white/85 backdrop-blur-md p-8 rounded-3xl border border-white/50 shadow-sm">
                    <h3 class="text-lg font-black text-[#604B10] flex items-center gap-2 mb-6 border-b border-[#6E5003]/10 pb-4">
                        <x-heroicon-s-star class="w-5 h-5 text-amber-600"/>
                        Peer Evaluation Rating
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-center mb-6">
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-3xl font-black text-[#604B10]">{{ $submittedTotal }}</p>
                            <p class="text-xs font-bold text-[#876A1A] mt-1">Reviews Submitted</p>
                        </div>
                        <div class="bg-[#FDCB40]/5 p-4 rounded-2xl border border-[#FDCB40]/10">
                            <p class="text-3xl font-black text-[#604B10]">{{ $receivedTotal }}</p>
                            <p class="text-xs font-bold text-[#876A1A] mt-1">Reviews Received</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-[#876A1A] uppercase tracking-wider mb-3">Average Evaluation Scores</h4>
                        <div class="space-y-3">
                            @php
                                $dimensions = [
                                    'collaboration' => ['label' => 'Collaboration & Teamwork', 'color' => 'bg-amber-600'],
                                    'delivery' => ['label' => 'Quality of Delivery', 'color' => 'bg-amber-700'],
                                    'communication' => ['label' => 'Communication & Presence', 'color' => 'bg-amber-800'],
                                ];
                            @endphp
                            @foreach($dimensions as $dimKey => $dimVal)
                                @php
                                    $score = $receivedAverageScores[$dimKey] ?? 0.0;
                                    $percent = round(($score / 5.0) * 100);
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs font-bold text-[#6E5003]">
                                        <span>{{ $dimVal['label'] }}</span>
                                        <span>{{ $score }} / 5.0</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="h-full {{ $dimVal['color'] }} rounded-full" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="pt-6 flex justify-center pb-12">
            <form method="POST" action="{{ route('logout') }}" class="w-full max-w-sm">
                @csrf
                <button type="submit" class="w-full py-4 rounded-full bg-[#604B10] hover:bg-[#604B10]/95 text-white font-extrabold text-center shadow-md transition duration-150 cursor-pointer outline-none border-none flex items-center justify-center gap-2 text-base">
                    <x-heroicon-s-arrow-left-on-rectangle class="w-5 h-5"/>
                    Logout from AmbatuWork
                </button>
            </form>
        </div>
    </div>
</div>
