<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('landing');

Route::get('/privacy', function() {
    return view('privacy');
})->name('privacy');

Route::get('/manual', function() {
    return view('manual.index');
})->name('manual.index');

Route::get('/manual/web', function() {
    return view('manual.web');
})->name('manual.web');

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
    return redirect()->away('https://youtu.be/8ZsrtSIBkf0');
});

// Website Project Submission
Route::get('/keynote-material-web', function() {
    return redirect()->away('https://drive.google.com/drive/folders/1JsHBJIsvWS7MNROrQxBUbyIZV-GAwwY0?usp=sharing');
});

Route::get('/keynote-video-web', function() {
    return redirect('https://youtu.be/0Hjlk3tFRWk');
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
    Route::livewire('/settings', 'settings')->name('settings');
    Route::livewire('/profile', 'profile')->name('profile');
    Route::livewire('/projects/{project}/sprints/{sprint}/retrospective', 'retrospective')->name('retrospective');
    Route::livewire('/projects/{project}/sprints/{sprint}/peer-review', 'peer-review')->name('peer-review');
    Route::livewire('/projects/create', 'create-project')->name('projects.create');
    Route::livewire('/backlog/create', 'create-backlog')->name('backlog.create');
    Route::livewire('/backlog/{backlogItem}/edit', 'edit-backlog')->name('backlog.edit');
    Route::livewire('/sprints/create', 'create-sprint')->name('sprints.create');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});