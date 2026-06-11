<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Debug\AvatarStorageController;
use App\Http\Controllers\Pos\PosFinanceController;
use App\Http\Controllers\Pos\PosProductController;
use App\Http\Controllers\Pos\PosSaleController;
use App\Http\Controllers\Pos\PosShiftController;
use App\Http\Controllers\Pos\PosTerminalController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserManagementController;
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

    Route::get('users', [UserManagementController::class, 'index'])
        ->middleware('can:'.AccessControl::PERMISSION_USERS_MANAGE)
        ->name('users.index');

    Route::patch('users/{user}', [UserManagementController::class, 'update'])
        ->middleware('can:'.AccessControl::PERMISSION_USERS_MANAGE)
        ->name('users.update');

    Route::resource('posts', PostController::class)
        ->middlewareFor(['index', 'show'], 'can:'.AccessControl::PERMISSION_POSTS_VIEW)
        ->middlewareFor(['create', 'store'], 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
        ->middlewareFor(['edit', 'update'], 'can:'.AccessControl::PERMISSION_POSTS_UPDATE)
        ->middlewareFor('destroy', 'can:'.AccessControl::PERMISSION_POSTS_DELETE);

    Route::get('pos', PosTerminalController::class)
        ->middleware('can:'.AccessControl::PERMISSION_POS_SALES_CREATE)
        ->name('pos.terminal');

    Route::post('pos/shifts', [PosShiftController::class, 'store'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SHIFTS_OPEN)
        ->name('pos.shifts.store');

    Route::get('pos/shifts', [PosShiftController::class, 'index'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SHIFTS_VIEW)
        ->name('pos.shifts.index');

    Route::patch('pos/shifts/{shift}/close', [PosShiftController::class, 'close'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SHIFTS_CLOSE)
        ->name('pos.shifts.close');

    Route::post('pos/shifts/{shift}/cash-movements', [PosShiftController::class, 'storeCashMovement'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SHIFTS_CLOSE)
        ->name('pos.shifts.cash-movements.store');

    Route::get('pos/products', [PosProductController::class, 'index'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_PRODUCTS_VIEW)
        ->name('pos.products.index');

    Route::post('pos/products', [PosProductController::class, 'store'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_PRODUCTS_MANAGE)
        ->name('pos.products.store');

    Route::patch('pos/products/{product}', [PosProductController::class, 'update'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_PRODUCTS_MANAGE)
        ->name('pos.products.update');

    Route::delete('pos/products/{product}', [PosProductController::class, 'destroy'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_PRODUCTS_MANAGE)
        ->name('pos.products.destroy');

    Route::post('pos/product-variants/{variant}/stock-adjustments', [PosProductController::class, 'adjustStock'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_INVENTORY_MANAGE)
        ->name('pos.product-variants.stock-adjustments.store');

    Route::get('pos/sales', [PosSaleController::class, 'index'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SALES_VIEW)
        ->name('pos.sales.index');

    Route::post('pos/sales', [PosSaleController::class, 'store'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SALES_CREATE)
        ->name('pos.sales.store');

    Route::patch('pos/sales/{sale}/void', [PosSaleController::class, 'void'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SALES_VOID)
        ->name('pos.sales.void');

    Route::get('pos/sales/{sale}', [PosSaleController::class, 'show'])
        ->middleware('can:'.AccessControl::PERMISSION_POS_SALES_VIEW)
        ->name('pos.sales.show');

    Route::get('pos/finance', PosFinanceController::class)
        ->middleware('can:'.AccessControl::PERMISSION_POS_FINANCE_VIEW)
        ->name('pos.finance.index');
});

Route::get('debug/avatar-storage', AvatarStorageController::class)
    ->middleware('auth')
    ->name('debug.avatar-storage');

require __DIR__.'/settings.php';
