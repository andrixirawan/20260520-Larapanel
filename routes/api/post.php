<?php

use App\Http\Controllers\Api\Post\PublicPostController;
use App\Http\Controllers\Api\Mobile\Post\PostController;
use Illuminate\Support\Facades\Route;

Route::get('posts', [PublicPostController::class, 'index'])->name('api.posts.index');
Route::get('posts/{post:slug}', [PublicPostController::class, 'show'])->name('api.posts.show');

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::middleware('mobile.auth')->group(function () {
        Route::get('posts', [PostController::class, 'index'])
            ->name('posts.index');

        Route::get('posts/all', [PostController::class, 'index'])
            ->name('posts.index-all')
            ->defaults('scope', 'all');

        Route::get('posts/mine', [PostController::class, 'index'])
            ->name('posts.index-mine')
            ->defaults('scope', 'mine');

        Route::apiResource('posts', PostController::class)
            ->except(['index'])
            ->middlewareFor('show', [
                'can:view,post',
            ])
            ->middlewareFor('update', [
                'can:update,post',
            ])
            ->middlewareFor('destroy', [
                'can:delete,post',
            ]);
    });
});
