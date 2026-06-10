<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\PostController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('auth.register');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:6,1')
        ->name('auth.forgot-password');

    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('auth.reset-password');

    Route::middleware('mobile.auth')->group(function () {
        Route::get('user', [AuthController::class, 'user'])->name('user');
        Route::apiResource('posts', PostController::class)
            ->middlewareFor(['index', 'show'], 'can:'.AccessControl::PERMISSION_POSTS_VIEW)
            ->middlewareFor('store', 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
            ->middlewareFor('update', 'can:'.AccessControl::PERMISSION_POSTS_UPDATE)
            ->middlewareFor('destroy', 'can:'.AccessControl::PERMISSION_POSTS_DELETE);
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
        Route::post('email/verification-notification', [AuthController::class, 'sendVerificationNotification'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});
