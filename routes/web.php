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

// Android Project Submission
Route::get('/keynote-material-android', function() {
    return redirect()->away('https://drive.google.com/drive/folders/1Huoi5Z3ogpXaEnHxWcW6IJKu2yYDpMSt?usp=sharing');
});

Route::get('/download-android', function() {
    return redirect()->away('https://github.com/ambawin/ambatuwork-android/releases');
});

Route::get('/keynote-video-android', function() {
    return redirect()->away('https://github.com/ambawin/ambatuwork-android/releases');
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
    Route::livewire('/notifications', 'notifications')->name('notifications');
    Route::livewire('/projects/{project}/sprints/{sprint}/retrospective', 'retrospective')->name('retrospective');
    Route::livewire('/projects/{project}/sprints/{sprint}/peer-review', 'peer-review')->name('peer-review');
    Route::livewire('/projects/create', 'create-project')->name('projects.create');
    Route::livewire('/backlog/create', 'create-backlog')->name('backlog.create');
    Route::livewire('/sprints/create', 'create-sprint')->name('sprints.create');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});