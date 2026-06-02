<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImpedimentRequest;
use App\Http\Resources\ImpedimentResource;
use App\Models\Impediment;
use App\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImpedimentController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', [Impediment::class, $project]);

        $impediments = $project->impediments()
            ->with(['reporter', 'owner'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ImpedimentResource::collection($impediments);
    }

    public function store(StoreImpedimentRequest $request, Project $project): ImpedimentResource
    {
        $this->authorize('create', [Impediment::class, $project]);

        $activeSprint = $project->activeSprint;

        $impediment = $project->impediments()->create(array_merge($request->validated(), [
            'sprint_id' => $activeSprint?->id,
            'reported_by_user_id' => $request->user()->id,
            'status' => 'open',
        ]));

        return new ImpedimentResource($impediment->load(['reporter', 'owner']));
    }

    public function update(StoreImpedimentRequest $request, Project $project, Impediment $impediment): ImpedimentResource
    {
        $this->authorize('update', [Impediment::class, $project]);

        abort_unless($impediment->project_id === $project->id, 404);

        $data = $request->validated();

        if (isset($data['status'])) {
            if ($data['status'] === 'resolved' && $impediment->status !== 'resolved') {
                $data['resolved_at'] = now();
            } elseif ($data['status'] !== 'resolved') {
                $data['resolved_at'] = null;
            }
        }

        $impediment->update($data);

        return new ImpedimentResource($impediment->load(['reporter', 'owner']));
    }

    public function resolve(Project $project, Impediment $impediment): ImpedimentResource
    {
        $this->authorize('update', [Impediment::class, $project]);

        abort_unless($impediment->project_id === $project->id, 404);

        $impediment->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return new ImpedimentResource($impediment->load(['reporter', 'owner']));
    }
}
