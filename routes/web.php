<?php

use App\Http\Controllers\Storefront\DestinationController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\PlanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiagnosticsController as AdminDiagnosticsController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\StorefrontPreviewController as AdminStorefrontPreviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/destinations/{slug}', [DestinationController::class, 'show'])->name('destinations.show');
Route::get('/united-arab-emirates-esim', [DestinationController::class, 'show'])->defaults('slug', 'united-arab-emirates')->name('country.uae');
Route::get('/plans/{plan:slug}', [PlanController::class, 'show'])->name('plans.show');
Route::get('/checkout/{plan:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('/checkout/{plan:slug}/payment', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::post('/checkout/{plan:slug}/stripe', [CheckoutController::class, 'stripe'])->name('checkout.stripe');
Route::get('/checkout/{plan:slug}/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/auth/login', [AuthenticatedSessionController::class, 'create'])->name('auth.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::get('/auth/signup', [RegisteredUserController::class, 'create'])->name('auth.signup');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed:relative', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/diagnostics', AdminDiagnosticsController::class)->name('diagnostics');
    Route::post('/diagnostics/test-esim-api', [AdminDiagnosticsController::class, 'testEsimApi'])->name('diagnostics.test-esim-api');
    Route::post('/diagnostics/sync-catalogue', [AdminDiagnosticsController::class, 'syncCatalogue'])->name('diagnostics.sync-catalogue');
    Route::get('/storefront', AdminStorefrontPreviewController::class)->name('storefront');
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/country/{country}', [AdminPlanController::class, 'country'])->name('plans.country');
    Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
    Route::get('/promotions', [AdminPromotionController::class, 'index'])->name('promotions.index');
    Route::post('/promotions', [AdminPromotionController::class, 'store'])->name('promotions.store');
    Route::patch('/promotions/{promotion}', [AdminPromotionController::class, 'update'])->name('promotions.update');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
});

Route::get('/{country}-esim', [DestinationController::class, 'show'])->name('country.esim');
