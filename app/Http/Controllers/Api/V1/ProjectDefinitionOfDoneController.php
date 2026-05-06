<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DefinitionOfDoneRequest;
use App\Http\Resources\DefinitionOfDoneResource;
use App\Models\DefinitionOfDone;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectDefinitionOfDoneController extends Controller
{
    public function show(Request $request, Project $project): DefinitionOfDoneResource
    {
        $this->authorize('view', $project);

        $definitionOfDone = $project->activeDefinitionOfDone()->firstOrFail();

        return new DefinitionOfDoneResource($definitionOfDone);
    }

    public function upsert(DefinitionOfDoneRequest $request, Project $project): JsonResponse
    {
        $definitionOfDone = $project->activeDefinitionOfDone()->first();
        $validated = $request->validated();

        if ($definitionOfDone) {
            $this->authorize('manageDefinitionOfDone', $project);

            $definitionOfDone->update([
                'title' => $validated['title'] ?? $definitionOfDone->title,
                'checklist' => $validated['checklist'] ?? $definitionOfDone->checklist,
            ]);

            return (new DefinitionOfDoneResource($definitionOfDone->refresh()))->response();
        }

        $this->authorize('manageDefinitionOfDone', $project);

        $definitionOfDone = $project->definitionsOfDone()->create([
            'title' => $validated['title'] ?? DefinitionOfDone::defaultTitle(),
            'checklist' => $validated['checklist'] ?? DefinitionOfDone::defaultChecklist(),
            'is_active' => true,
            'created_by_user_id' => $request->user()->id,
        ]);

        return (new DefinitionOfDoneResource($definitionOfDone))->response()->setStatusCode(201);
    }
}