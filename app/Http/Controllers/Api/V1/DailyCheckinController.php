<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyCheckinRequest;
use App\Http\Resources\DailyCheckinResource;
use App\Models\DailyCheckin;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class DailyCheckinController extends Controller
{
    public function index(Project $project, Sprint $sprint): AnonymousResourceCollection
    {
        $this->authorize('view', [DailyCheckin::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $checkins = $sprint->dailyCheckins()
            ->with('user')
            ->orderBy('checkin_date', 'desc')
            ->get();

        return DailyCheckinResource::collection($checkins);
    }

    public function store(StoreDailyCheckinRequest $request, Project $project, Sprint $sprint): DailyCheckinResource
    {
        $this->authorize('create', [DailyCheckin::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        if ($sprint->status !== 'active') {
            throw ValidationException::withMessages([
                'sprint' => ['You can only submit check-ins for active sprints.'],
            ]);
        }

        $checkin = DailyCheckin::create(array_merge($request->validated(), [
            'project_id' => $project->id,
            'sprint_id' => $sprint->id,
            'user_id' => $request->user()->id,
        ]));

        if ($request->filled('blockers')) {
            $project->impediments()->create([
                'sprint_id' => $sprint->id,
                'reported_by_user_id' => $request->user()->id,
                'title' => 'Blocker reported by ' . $request->user()->name . ' on ' . $checkin->checkin_date->toDateString(),
                'description' => $request->input('blockers'),
                'status' => 'open',
            ]);
        }

        return new DailyCheckinResource($checkin->load('user'));
    }
}
