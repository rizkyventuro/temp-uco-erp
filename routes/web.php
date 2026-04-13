<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\AccountsPayableController;
use App\Http\Controllers\AccountsReceivableController;
use App\Http\Controllers\Auth\VerifyEmailController as CustomVerifyEmailController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\CashBankController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoodsIssueController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\ManagementUserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
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



    Route::prefix('master-data')->name('master-data.')->group(function () {
        // Supplier
        Route::prefix('supplier')->name('supplier.')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('index');
            Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
            Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
            Route::post('/', [SupplierController::class, 'store'])->name('store');
            Route::patch('/{supplier}', [SupplierController::class, 'update'])->name('update');
            Route::patch('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
        });

        // Buyer
        Route::prefix('buyer')->name('buyer.')->group(function () {
            Route::get('/', [BuyerController::class, 'index'])->name('index');
            Route::get('/{buyer}', [BuyerController::class, 'show'])->name('show');
            Route::get('/{buyer}/edit', [BuyerController::class, 'edit'])->name('edit');
            Route::post('/', [BuyerController::class, 'store'])->name('store');
            Route::patch('/{buyer}', [BuyerController::class, 'update'])->name('update');
            Route::patch('/{buyer}/toggle-status', [BuyerController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{buyer}', [BuyerController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('warehouse')->name('warehouse.')->group(function () {
            Route::get('/',          [WarehouseController::class, 'index'])->name('index');
            Route::post('/',         [WarehouseController::class, 'store'])->name('store');
            Route::post('/transfer', [WarehouseController::class, 'transfer'])->name('transfer');
            Route::get('/{id}',      [WarehouseController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [WarehouseController::class, 'edit'])->name('edit');
            Route::patch('/{id}',    [WarehouseController::class, 'update'])->name('update');
            Route::delete('/{id}',   [WarehouseController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/toggle-status', [WarehouseController::class, 'toggleStatus'])
                ->name('toggle-status');
        });
    });


    // Barang Masuk
    Route::prefix('goods-receipt')->name('goods-receipt.')->group(function () {
        Route::get('/',                              [GoodsReceiptController::class, 'index'])->name('index');
        Route::get('/{id}',                          [GoodsReceiptController::class, 'show'])->name('show');
        Route::post('/',                             [GoodsReceiptController::class, 'store'])->name('store');
        Route::patch('/{id}',                        [GoodsReceiptController::class, 'update'])->name('update');
        Route::patch('/{id}/update-status',          [GoodsReceiptController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}',                       [GoodsReceiptController::class, 'destroy'])->name('destroy');
    });

    // Barang Keluar
    Route::prefix('goods-issue')->name('goods-issue.')->group(function () {
        Route::get('/', [GoodsIssueController::class, 'index'])->name('index');
        Route::get('/{id}', [GoodsIssueController::class, 'show'])->name('show');
        Route::post('/', [GoodsIssueController::class, 'store'])->name('store');
        Route::patch('/{id}', [GoodsIssueController::class, 'update'])->name('update');
        Route::patch('/{id}/update-status', [GoodsIssueController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}',                       [GoodsIssueController::class, 'destroy'])->name('destroy');
    });

    // Transfer Stok
    Route::prefix('stock-transfer')->name('stock-transfer.')->group(function () {
        Route::get('/',              [StockTransferController::class, 'index'])->name('index');
        Route::post('/',             [StockTransferController::class, 'store'])->name('store');
        Route::patch('/{id}/status', [StockTransferController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}',       [StockTransferController::class, 'destroy'])->name('destroy');
    });

    // Stok / Opname
    Route::prefix('stock-opname')->name('stock-opname.')->group(function () {
        Route::get('/', [StockOpnameController::class, 'index'])->name('index');
    });

    // Hutang (AP)
    Route::prefix('accounts-payable')->name('accounts-payable.')->group(function () {
        Route::get('/', [AccountsPayableController::class, 'index'])->name('index');
    });

    // Piutang (AR)
    Route::prefix('accounts-receivable')->name('accounts-receivable.')->group(function () {
        Route::get('/', [AccountsReceivableController::class, 'index'])->name('index');
    });

    // Kas / Bank
    Route::prefix('cash-bank')->name('cash-bank.')->group(function () {
        Route::get('/', [CashBankController::class, 'index'])->name('index');
    });

    // Laporan
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });
});


require __DIR__ . '/settings.php';

Route::fallback(function () {
    return Inertia::render('errors/Error404');
});
