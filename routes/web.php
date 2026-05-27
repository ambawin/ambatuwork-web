<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('landing');

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});