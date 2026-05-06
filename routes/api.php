<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProjectBacklogItemController;
use App\Http\Controllers\Api\V1\ProjectDefinitionOfDoneController;
use App\Http\Controllers\Api\V1\ProjectInvitationController;
use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/google', [AuthController::class, 'google']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('projects', ProjectController::class)->only([
            'index',
            'store',
            'show',
            'update',
        ]);

        Route::get('/projects/{project}/definition-of-done', [ProjectDefinitionOfDoneController::class, 'show']);
        Route::patch('/projects/{project}/definition-of-done', [ProjectDefinitionOfDoneController::class, 'upsert']);

        Route::get('/projects/{project}/backlog-items', [ProjectBacklogItemController::class, 'index']);
        Route::post('/projects/{project}/backlog-items', [ProjectBacklogItemController::class, 'store']);
        Route::get('/projects/{project}/backlog-items/{backlogItem}', [ProjectBacklogItemController::class, 'show']);
        Route::patch('/projects/{project}/backlog-items/{backlogItem}', [ProjectBacklogItemController::class, 'update']);
        Route::delete('/projects/{project}/backlog-items/{backlogItem}', [ProjectBacklogItemController::class, 'destroy']);

        Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
        Route::patch('/projects/{project}/members/{user}', [ProjectMemberController::class, 'update']);
        Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);

        Route::get('/invitations', [ProjectInvitationController::class, 'index']);
        Route::get('/projects/{project}/invitations', [ProjectInvitationController::class, 'projectIndex']);
        Route::delete('/projects/{project}/invitations/{invitation}', [ProjectInvitationController::class, 'destroy']);
        Route::post('/projects/{project}/invitations', [ProjectInvitationController::class, 'store']);
        Route::post('/invitations/{token}/accept', [ProjectInvitationController::class, 'accept']);
    });
});