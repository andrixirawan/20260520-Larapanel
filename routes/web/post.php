<?php

use App\Http\Controllers\Post\PostController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'home'])->name('home');
Route::get('posts/{post}/cover', [PostController::class, 'cover'])->name('posts.cover');
Route::get('p/{post:slug}', [PostController::class, 'publicShow'])->name('public.posts.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('posts', [PostController::class, 'index'])
        ->name('posts.index')
        ->middleware('can:'.AccessControl::PERMISSION_POSTS_VIEW);

    Route::resource('posts', PostController::class)
        ->except(['index'])
        ->middlewareFor('show', [
            'can:'.AccessControl::PERMISSION_POSTS_VIEW,
            'can:view,post',
        ])
        ->middlewareFor(['create', 'store'], 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
        ->middlewareFor(['edit', 'update'], [
            'can:'.AccessControl::PERMISSION_POSTS_UPDATE,
            'can:update,post',
        ])
        ->middlewareFor('destroy', [
            'can:'.AccessControl::PERMISSION_POSTS_DELETE,
            'can:delete,post',
        ]);
});
