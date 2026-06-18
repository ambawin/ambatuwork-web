<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProjectBacklogItemController;
use App\Http\Controllers\Api\V1\ProjectDefinitionOfDoneController;
use App\Http\Controllers\Api\V1\ProjectInvitationController;
use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SprintBoardController;
use App\Http\Controllers\Api\V1\SprintController;
use App\Http\Controllers\Api\V1\SprintLifecycleController;
use App\Http\Controllers\Api\V1\DailyCheckinController;
use App\Http\Controllers\Api\V1\ImpedimentController;
use App\Http\Controllers\Api\V1\SprintReviewController;
use App\Http\Controllers\Api\V1\RetrospectiveController;
use App\Http\Controllers\Api\V1\PeerReviewController;
use App\Http\Controllers\Api\V1\ProjectStatsController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/google', [AuthController::class, 'google']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/device-token', [UserController::class, 'registerFcmToken']);
        Route::get('/users/me/stats', [UserController::class, 'stats']);

        Route::apiResource('projects', ProjectController::class)->only([
            'index',
            'store',
            'show',
            'update',
        ]);

        Route::get('/projects/{project}/stats', [ProjectStatsController::class, 'show']);

        Route::get('/projects/{project}/definition-of-done', [ProjectDefinitionOfDoneController::class, 'show']);
        Route::patch('/projects/{project}/definition-of-done', [ProjectDefinitionOfDoneController::class, 'upsert']);

        Route::get('/projects/{project}/backlog-items', [ProjectBacklogItemController::class, 'index']);
        Route::post('/projects/{project}/backlog-items', [ProjectBacklogItemController::class, 'store']);
        Route::get('/projects/{project}/backlog-items/{backlogItem}', [ProjectBacklogItemController::class, 'show']);
        Route::patch('/projects/{project}/backlog-items/{backlogItem}', [ProjectBacklogItemController::class, 'update']);
        Route::delete('/projects/{project}/backlog-items/{backlogItem}', [ProjectBacklogItemController::class, 'destroy']);

        Route::get('/projects/{project}/sprints', [SprintController::class, 'index']);
        Route::post('/projects/{project}/sprints', [SprintController::class, 'store']);
        Route::get('/projects/{project}/sprints/{sprint}', [SprintController::class, 'show']);
        Route::post('/projects/{project}/sprints/{sprint}/start', [SprintLifecycleController::class, 'start']);
        Route::post('/projects/{project}/sprints/{sprint}/close', [SprintLifecycleController::class, 'close']);
        Route::get('/projects/{project}/sprints/{sprint}/board', [SprintBoardController::class, 'show']);

        // Daily Check-ins
        Route::get('/projects/{project}/sprints/{sprint}/checkins', [DailyCheckinController::class, 'index']);
        Route::post('/projects/{project}/sprints/{sprint}/checkins', [DailyCheckinController::class, 'store']);

        // Impediments/Blockers
        Route::get('/projects/{project}/impediments', [ImpedimentController::class, 'index']);
        Route::post('/projects/{project}/impediments', [ImpedimentController::class, 'store']);
        Route::patch('/projects/{project}/impediments/{impediment}', [ImpedimentController::class, 'update']);
        Route::post('/projects/{project}/impediments/{impediment}/resolve', [ImpedimentController::class, 'resolve']);

        // Sprint Review
        Route::get('/projects/{project}/sprints/{sprint}/review', [SprintReviewController::class, 'show']);
        Route::post('/projects/{project}/sprints/{sprint}/review', [SprintReviewController::class, 'store']);

        // Retrospective
        Route::get('/projects/{project}/sprints/{sprint}/retrospective', [RetrospectiveController::class, 'show']);
        Route::post('/projects/{project}/sprints/{sprint}/retrospective', [RetrospectiveController::class, 'store']);
        Route::post('/projects/{project}/sprints/{sprint}/retrospective/items', [RetrospectiveController::class, 'storeItem']);
        Route::patch('/projects/{project}/sprints/{sprint}/retrospective/items/{retroItem}', [RetrospectiveController::class, 'updateItem']);
        Route::delete('/projects/{project}/sprints/{sprint}/retrospective/items/{retroItem}', [RetrospectiveController::class, 'destroyItem']);

        // Peer Reviews
        Route::get('/projects/{project}/sprints/{sprint}/peer-review-cycle', [PeerReviewController::class, 'showCycle']);
        Route::post('/projects/{project}/sprints/{sprint}/peer-review-cycle', [PeerReviewController::class, 'storeCycle']);
        Route::post('/projects/{project}/peer-review-cycles/{cycle}/reviews', [PeerReviewController::class, 'submitReview']);
        Route::post('/projects/{project}/peer-review-cycles/{cycle}/close', [PeerReviewController::class, 'closeCycle']);
        Route::get('/projects/{project}/peer-review-cycles/{cycle}/summary', [PeerReviewController::class, 'summary']);
        Route::get('/projects/{project}/peer-review-cycles/{cycle}/my-summary', [PeerReviewController::class, 'mySummary']);

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