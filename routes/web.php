<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Auth\CompleteProfileController;
use App\Http\Controllers\Auth\VerifyEmailController as CustomVerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KtpVerificationController;
use App\Http\Controllers\ManagementPooController;
use App\Http\Controllers\ManagementUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PooCollectionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UCOExportController;
use App\Http\Controllers\UCOHistoryController;
use App\Http\Controllers\UCOTransferController;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferensiLocationSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('public/Welcome');
// })->name('home');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::post('/auth/google', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'store'])->name('auth.google');

Route::get('/email/verify/{id}/{hash}', CustomVerifyEmailController::class)
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

Route::get('/ktp', [KtpVerificationController::class, 'index']);
Route::post('/ktp/extract', [KtpVerificationController::class, 'extract']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/run-permission-seeder', function () {
        $seeder = new PermissionSeeder();
        $seeder->run();


        return 'PermissionSeeder berhasil dijalankan!';
    });



    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Complete Profile
    Route::prefix('complete-profile')->name('complete-profile.')->group(function () {
        Route::get('/', [CompleteProfileController::class, 'show'])->name('show');
        Route::post('/', [CompleteProfileController::class, 'store'])->name('store');
        Route::post('/ocr-ktp', [KtpVerificationController::class, 'extractForProfile']);
    });

    // Management POO
    Route::prefix('management-poo')->name('management-poo.')->middleware('can:' . PermissionEnum::VIEW_MASTER_POO->value)->group(function () {
        Route::get('/', [ManagementPooController::class, 'index'])->name('index');
        Route::post('/', [ManagementPooController::class, 'store'])->name('store');
        Route::put('/{id}', [ManagementPooController::class, 'update'])->name('update');
        Route::delete('/{id}', [ManagementPooController::class, 'destroy'])->name('destroy');
    });

    // Management User
    Route::prefix('management-user')->name('management-user.')->middleware('can:' . PermissionEnum::VIEW_USER->value)->group(function () {
        Route::get('/', [ManagementUserController::class, 'index'])->name('index');
        Route::post('/', [ManagementUserController::class, 'store'])->name('store');
        Route::put('/{id}', [ManagementUserController::class, 'update'])->name('update');
        Route::delete('/{id}', [ManagementUserController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/verify', [ManagementUserController::class, 'verify'])->name('verify');
        Route::patch('/{user}/reject', [ManagementUserController::class, 'reject'])->name('reject');
    });

    // Pengambilan POO
    Route::prefix('collections')->name('collections.')->middleware('can:' . PermissionEnum::VIEW_PENGAMBILAN_POO->value)->group(function () {
        Route::get('/', [PooCollectionController::class, 'index'])->name('index');
        Route::get('/{pooId}/create', [PooCollectionController::class, 'create'])->name('create');
        Route::post('/', [PooCollectionController::class, 'store'])->name('store');
        Route::get('/{collactId}/success', [PooCollectionController::class, 'success'])->name('success');
    });

    // Transfer UCO
    Route::prefix('transfers')->name('transfers.')->middleware('can:' . PermissionEnum::VIEW_TRANSFER->value)->group(function () {
        Route::get('/', [UCOTransferController::class, 'index'])->name('index');
        Route::get('/create', [UCOTransferController::class, 'create'])->name('create');
        Route::post('/', [UCOTransferController::class, 'store'])->name('store');
        Route::get('/show/{transfer_code}', [UCOTransferController::class, 'show'])->name('show');
        Route::get('/claim', [UCOTransferController::class, 'claim'])->name('claim');
        Route::post('/confirm-claim', [UCOTransferController::class, 'confirmClaim'])->name('confirmClaim');
        Route::post('/preview', [UCOTransferController::class, 'preview'])->name('transfers.preview');
    });

    // History UCO
    Route::get('/history', [UCOHistoryController::class, 'index'])
        ->middleware('can:' . PermissionEnum::VIEW_RIWAYAT->value)
        ->name('history.index');

    // Notification
    Route::prefix('notifications')->name('notifications.')->middleware('can:' . PermissionEnum::VIEW_NOTIFICATION->value)->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('readAll');
        Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    // Role
    Route::prefix('roles')->name('roles.')->middleware('can:' . PermissionEnum::VIEW_ROLE->value)->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::patch('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    // Export UCO
    Route::prefix('exports')->name('exports.')->middleware('can:' . PermissionEnum::VIEW_PENJUALAN->value)->group(function () {
        Route::get('/', [UCOExportController::class, 'index'])->name('index');
        Route::get('/{batch}/confirmation', [UCOExportController::class, 'confirmation'])->name('confirmation');
        Route::post('/{batch}/generate', [UCOExportController::class, 'generate'])->name('generate');
        Route::get('/{exportId}/success', [UCOExportController::class, 'success'])->name('success');
        Route::get('/{exportId}/iscc-preview', [UCOExportController::class, 'previewIscc'])->name('preview-iscc');
        Route::get('/{exportId}/download', [UCOExportController::class, 'download'])->name('download');
        Route::get('/{exportId}/download-stored', [UCOExportController::class, 'downloadStoredIscc'])->name('download-stored');
    });
});

Route::get('/log-viewer', function () {
    $logPath = storage_path('logs/laravel.log');

    if (!file_exists($logPath)) {
        abort(404, 'Log file tidak ditemukan.');
    }

    // Ambil 500 baris terakhir agar tidak berat
    $lines = array_slice(file($logPath), -500);

    return response(implode('', $lines))
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->middleware(['auth'])->name('log.viewer');

require __DIR__ . '/settings.php';

Route::fallback(function () {
    return Inertia::render('errors/Error404');
});
