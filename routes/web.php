<?php

use App\Http\Controllers\AccountTransactionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('can:manageUsers,'.User::class)->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });

    Route::resource('accounts', FinancialAccountController::class)
        ->except(['destroy'])
        ->parameters(['accounts' => 'account']);
    Route::post('/accounts/{account}/transactions', [AccountTransactionController::class, 'store'])
        ->name('accounts.transactions.store');
    Route::post('/accounts/{account}/transactions/{transaction}/reversal', [AccountTransactionController::class, 'reverse'])
        ->name('accounts.transactions.reverse');
});
