<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserManagement;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::prefix('auth')->middleware('guest')->group(function () {
    Route::get('/sign-in',  [AuthController::class, 'index'])->name('login');
    Route::post('/sign-in', [AuthController::class, 'login'])->name('login-proses');
});

Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'branch.access'])->group(function () {
    Route::post('/branch/switch', [BranchSwitchController::class, 'switch'])->name('branch.switch');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    Route::get('/shift/status', [ShiftController::class, 'status'])->name('shifts.status');
    Route::prefix('shift')->name('shifts.')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::post('/buka', [ShiftController::class, 'open'])->name('open');
        Route::get('/{shift}', [ShiftController::class, 'show'])->name('show');
        Route::post('/{shift}/tutup', [ShiftController::class, 'close'])->name('close');
    });

    // Produk
    Route::prefix('produk')->name('products.')->group(function () {
        Route::get('/',                         [ProductController::class, 'index'])->name('index');
        Route::get('/tambah',                   [ProductController::class, 'create'])->name('create');
        Route::post('/',                        [ProductController::class, 'store'])->name('store');
        Route::get('/{product}',                [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit',           [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}',                [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}',             [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/stok',          [ProductController::class, 'adjustStock'])->name('adjust-stock');
    });

    // Jasa (servis & keuangan)
    Route::prefix('jasa')->name('services.')->group(function () {
        Route::get('/',                [ServiceController::class, 'index'])->name('index');
        Route::get('/tambah',          [ServiceController::class, 'create'])->name('create');
        Route::post('/',               [ServiceController::class, 'store'])->name('store');
        Route::get('/{service}/edit',  [ServiceController::class, 'edit'])->name('edit');
        Route::put('/{service}',       [ServiceController::class, 'update'])->name('update');
        Route::delete('/{service}',    [ServiceController::class, 'destroy'])->name('destroy');
        Route::post('/{service}/jual', [ServiceController::class, 'sell'])->name('sell');
    });

    // Kategori produk
    Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');

    Route::prefix('user-management')->name('user-manage.')->middleware('role:owner')->group(function () {
        Route::get('/',                          [UserManagement::class, 'index'])->name('index');
        Route::post('/',                         [UserManagement::class, 'store'])->name('store');
        Route::put('/{user}',                    [UserManagement::class, 'update'])->name('update');
        Route::delete('/{user}',                 [UserManagement::class, 'destroy'])->name('destroy');
        Route::post('/{user}/reset-password',    [UserManagement::class, 'resetPassword'])->name('reset-password');
    });

    Route::prefix('transaksi')->name('transactions.')->group(function () {
        Route::get('/',                     [TransactionController::class, 'index'])->name('index');
        Route::post('/',                    [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}',        [TransactionController::class, 'show'])->name('show');
        Route::post('/{transaction}/void',  [TransactionController::class, 'void'])->name('void');
        Route::post('/{transaction}/retur', [TransactionController::class, 'processReturn'])->name('return');
    });

    // Rekening bank & e-wallet (saldo)
    Route::prefix('bank')->name('banks.')->group(function () {
        Route::get('/',         [BankController::class, 'index'])->name('index');
        Route::post('/',        [BankController::class, 'store'])->name('store');
        Route::put('/{bank}',   [BankController::class, 'update'])->name('update');
        Route::delete('/{bank}',[BankController::class, 'destroy'])->name('destroy');
    });
});
