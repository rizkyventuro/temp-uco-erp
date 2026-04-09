<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Auth\VerifyEmailController as CustomVerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagementUserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
  
    // Management User
    Route::prefix('management-user')->name('management-user.')->middleware('can:' . PermissionEnum::VIEW_USER->value)->group(function () {
        Route::get('/', [ManagementUserController::class, 'index'])->name('index');
        Route::post('/', [ManagementUserController::class, 'store'])->name('store');
        Route::put('/{id}', [ManagementUserController::class, 'update'])->name('update');
        Route::delete('/{id}', [ManagementUserController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/verify', [ManagementUserController::class, 'verify'])->name('verify');
        Route::patch('/{user}/reject', [ManagementUserController::class, 'reject'])->name('reject');
    });

    // Role
    Route::prefix('management-role')->name('roles.')->middleware('can:' . PermissionEnum::VIEW_ROLE->value)->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::patch('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

});


require __DIR__ . '/settings.php';

Route::fallback(function () {
    return Inertia::render('errors/Error404');
});
