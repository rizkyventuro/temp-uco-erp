<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Auth\VerifyEmailController as CustomVerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagementUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
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
        Route::get('/create', [ManagementUserController::class, 'create'])->name('create');
        Route::post('/', [ManagementUserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ManagementUserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ManagementUserController::class, 'update'])->name('update');
        Route::delete('/{id}', [ManagementUserController::class, 'destroy'])->name('destroy');
    });

    // Role
    Route::prefix('management-role')->name('roles.')->middleware('can:' . PermissionEnum::VIEW_ROLE->value)->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::patch('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });



    // Supplier
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::prefix('supplier')->name('supplier.')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('index');
            Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
            Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
            Route::post('/', [SupplierController::class, 'store'])->name('store');
            Route::patch('/{supplier}', [SupplierController::class, 'update'])->name('update');
            Route::patch('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
        });
    });
});


require __DIR__ . '/settings.php';

Route::fallback(function () {
    return Inertia::render('errors/Error404');
});
