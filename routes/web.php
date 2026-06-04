<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('landing');

Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/privacy', function() {
    return view('privacy');
})->name('privacy');

Route::get('/zidan', function() {
    return 'hello zidan!';
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/auth/google/callback', [WebAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/backlog', 'backlog')->name('backlog');
    Route::livewire('/sprint-board', 'sprint-board')->name('sprint-board');
    Route::livewire('/projects/create', 'create-project')->name('projects.create');
    Route::livewire('/backlog/create', 'create-backlog')->name('backlog.create');
    Route::livewire('/sprints/create', 'create-sprint')->name('sprints.create');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});