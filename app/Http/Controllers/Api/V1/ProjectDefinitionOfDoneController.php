<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DefinitionOfDoneRequest;
use App\Http\Resources\DefinitionOfDoneResource;
use App\Models\DefinitionOfDone;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class ProjectDefinitionOfDoneController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/definition-of-done',
        summary: 'Get Definition of Done',
        description: 'Returns the active Definition of Done checklist for the specified project.',
        tags: ['Definition of Done'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/DefinitionOfDone')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Definition of Done not found')
        ]
    )]
    public function show(Request $request, Project $project): DefinitionOfDoneResource
    {
        $this->authorize('view', $project);

        $definitionOfDone = $project->activeDefinitionOfDone()->firstOrFail();

        return new DefinitionOfDoneResource($definitionOfDone);
    }

    #[OA\Patch(
        path: '/projects/{project}/definition-of-done',
        summary: 'Create or Update Definition of Done',
        description: 'Creates or updates the active Definition of Done checklist for the specified project.',
        tags: ['Definition of Done'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Team Definition of Done'),
                    new OA\Property(
                        property: 'checklist',
                        type: 'array',
                        items: new OA\Items(type: 'string', maxLength: 500),
                        example: ['Tests pass', 'Code reviewed', 'Documentation updated']
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Definition of Done updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/DefinitionOfDone')
                    ]
                )
            ),
            new OA\Response(
                response: 201,
                description: 'Definition of Done created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/DefinitionOfDone')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
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