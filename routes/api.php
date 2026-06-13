<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EntityController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\PreferenceController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public auth endpoints.
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')->name('api.auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')->name('api.auth.login');
    Route::post('auth/social/{provider}', [SocialAuthController::class, 'store'])
        ->middleware('throttle:10,1')->name('api.auth.social');

    Route::get('currencies', [PreferenceController::class, 'index'])->name('api.currencies');

    // Authenticated endpoints.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

        Route::put('preferences', [PreferenceController::class, 'update'])->name('api.preferences.update');

        Route::get('dashboard', DashboardController::class)->name('api.dashboard');

        Route::get('categories', [CategoryController::class, 'index'])->name('api.categories.index');

        Route::get('wallets', [WalletController::class, 'index'])->name('api.wallets.index');
        Route::post('wallets', [WalletController::class, 'store'])->name('api.wallets.store');
        Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('api.wallets.update');
        Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->name('api.wallets.destroy');

        Route::get('transactions', [TransactionController::class, 'index'])->name('api.transactions.index');
        Route::post('transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('api.transactions.destroy');

        Route::get('analytics', AnalyticsController::class)->name('api.analytics');

        Route::get('trips', [TripController::class, 'index'])->name('api.trips.index');
        Route::post('trips', [TripController::class, 'store'])->name('api.trips.store');
        Route::get('trips/{trip}', [TripController::class, 'show'])->name('api.trips.show');
        Route::put('trips/{trip}', [TripController::class, 'update'])->name('api.trips.update');
        Route::delete('trips/{trip}', [TripController::class, 'destroy'])->name('api.trips.destroy');

        Route::get('loans', [LoanController::class, 'index'])->name('api.loans.index');
        Route::post('loans', [LoanController::class, 'store'])->name('api.loans.store');
        Route::patch('loans/{loan}/settle', [LoanController::class, 'settle'])->name('api.loans.settle');
        Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('api.loans.destroy');

        Route::get('entities', [EntityController::class, 'index'])->name('api.entities.index');
        Route::post('entities', [EntityController::class, 'store'])->name('api.entities.store');
        Route::put('entities/{entity}', [EntityController::class, 'update'])->name('api.entities.update');
        Route::delete('entities/{entity}', [EntityController::class, 'destroy'])->name('api.entities.destroy');
    });
});
