<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Debug\AvatarStorageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/google', [GoogleOAuthController::class, 'redirect'])
        ->name('auth.google.redirect');

    Route::get('auth/google/callback', [GoogleOAuthController::class, 'callback'])
        ->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::get('debug/avatar-storage', AvatarStorageController::class)
    ->middleware('auth')
    ->name('debug.avatar-storage');

require __DIR__.'/settings.php';
