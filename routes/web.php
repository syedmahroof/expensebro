<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AdminMiddleware;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'settings' => Setting::first(),
    ]);
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/{provider}/redirect', [SocialController::class, 'redirect'])->name('social.redirect');
    Route::get('auth/{provider}/callback', [SocialController::class, 'callback'])->name('social.callback');

    Route::get('login/whatsapp', [OtpController::class, 'showPhone'])->name('otp.phone');
    Route::post('login/whatsapp/send', [OtpController::class, 'sendOtp'])->name('otp.send');
    Route::get('login/whatsapp/verify/{phone}', [OtpController::class, 'showVerify'])->name('otp.verify');
    Route::post('login/whatsapp/verify', [OtpController::class, 'verifyOtp'])->name('otp.verify.submit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('onboarding/currency', [OnboardingController::class, 'storeCurrency'])->name('onboarding.currency');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');

    Route::get('ai', [AiChatController::class, 'index'])->name('ai.index');
    Route::post('ai/parse', [AiChatController::class, 'parse'])->name('ai.parse');
    Route::post('ai/save', [AiChatController::class, 'save'])->name('ai.save');

    Route::get('analytics', AnalyticsController::class)->name('analytics');

    Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::delete('groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('groups/{group}/expenses', [GroupController::class, 'storeExpense'])->name('groups.expenses.store');
    Route::delete('groups/{group}/expenses/{expense}', [GroupController::class, 'destroyExpense'])->name('groups.expenses.destroy');
    Route::patch('groups/{group}/splits/{split}/settle', [GroupController::class, 'settleSplit'])->name('groups.splits.settle');

    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
    Route::patch('loans/{loan}/settle', [LoanController::class, 'settle'])->name('loans.settle');
    Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

    Route::get('trips', [TripController::class, 'index'])->name('trips.index');
    Route::post('trips', [TripController::class, 'store'])->name('trips.store');
    Route::get('trips/{trip}', [TripController::class, 'show'])->name('trips.show');
    Route::put('trips/{trip}', [TripController::class, 'update'])->name('trips.update');
    Route::delete('trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
    Route::get('subscription', SubscriptionController::class)->name('subscription');
    Route::post('subscription/checkout', [SubscriptionCheckoutController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionCheckoutController::class, 'success'])->name('subscription.success');
    Route::get('subscription/portal', [SubscriptionCheckoutController::class, 'portal'])->name('subscription.portal');

    Route::get('entities', [EntityController::class, 'index'])->name('entities.index');
    Route::post('entities', [EntityController::class, 'store'])->name('entities.store');
    Route::put('entities/{entity}', [EntityController::class, 'update'])->name('entities.update');
    Route::delete('entities/{entity}', [EntityController::class, 'destroy'])->name('entities.destroy');

    Route::get('search', SearchController::class)->name('search');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminController::class)->name('index');
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/settings.php';
