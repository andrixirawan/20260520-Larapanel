<?php

use App\Http\Controllers\Post\PostController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'home'])->name('home');
Route::get('posts/{post}/cover', [PostController::class, 'cover'])->name('posts.cover');
Route::get('p/{post:slug}', [PostController::class, 'publicShow'])->name('public.posts.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('posts', PostController::class)
        ->middlewareFor(['index', 'show'], 'can:'.AccessControl::PERMISSION_POSTS_VIEW)
        ->middlewareFor(['create', 'store'], 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
        ->middlewareFor(['edit', 'update'], 'can:'.AccessControl::PERMISSION_POSTS_UPDATE)
        ->middlewareFor('destroy', 'can:'.AccessControl::PERMISSION_POSTS_DELETE);
});
