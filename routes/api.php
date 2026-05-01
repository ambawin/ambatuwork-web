<?php

use App\Http\Controllers\Api\V1\AuthController;
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

        Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
        Route::patch('/projects/{project}/members/{user}', [ProjectMemberController::class, 'update']);
        Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);

        Route::post('/projects/{project}/invitations', [ProjectInvitationController::class, 'store']);
        Route::post('/invitations/{token}/accept', [ProjectInvitationController::class, 'accept']);
    });
});