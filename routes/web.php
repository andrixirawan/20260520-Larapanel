<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Debug\AvatarStorageController;
use App\Http\Controllers\PostController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'home'])->name('home');
Route::get('posts/{post}/cover', [PostController::class, 'cover'])->name('posts.cover');
Route::get('p/{post:slug}', [PostController::class, 'publicShow'])->name('public.posts.show');

Route::get('auth/google', [GoogleOAuthController::class, 'redirect'])
    ->name('auth.google.redirect');

Route::get('auth/google/callback', [GoogleOAuthController::class, 'callback'])
    ->name('auth.google.callback');

Route::get('auth/google/redirect', [GoogleOAuthController::class, 'redirect'])
    ->middleware('throttle:6,1')
    ->name('auth.google.mobile.redirect');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('posts', PostController::class)
        ->middlewareFor(['index', 'show'], 'can:'.AccessControl::PERMISSION_POSTS_VIEW)
        ->middlewareFor(['create', 'store'], 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
        ->middlewareFor(['edit', 'update'], 'can:'.AccessControl::PERMISSION_POSTS_UPDATE)
        ->middlewareFor('destroy', 'can:'.AccessControl::PERMISSION_POSTS_DELETE);
});

Route::get('debug/avatar-storage', AvatarStorageController::class)
    ->middleware('auth')
    ->name('debug.avatar-storage');

require __DIR__.'/settings.php';
