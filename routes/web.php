<?php

use App\Http\Controllers\Storefront\DestinationController;
use App\Http\Controllers\Storefront\ContentPageController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\MyEsimController;
use App\Http\Controllers\Storefront\PlanController;
use App\Http\Controllers\Storefront\PlansIndexController;
use App\Http\Controllers\Storefront\SupportChatController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiagnosticsController as AdminDiagnosticsController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\StorefrontPreviewController as AdminStorefrontPreviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Models\EsimPlan;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', HomeController::class)->name('home');
Route::get('/how-blipblap-works', fn () => Inertia::render('Storefront/HowItWorks'))->name('how-it-works');
Route::get('/esim-plans', PlansIndexController::class)->name('plans.index');
Route::get('/destinations-list', function () {
    $flagMap = [
        'AE' => '/images/blipblap/ARE.svg',
        'EG' => '/images/blipblap/EGY.svg',
        'GB' => '/images/blipblap/GBR.svg',
        'OM' => '/images/blipblap/OMN.svg',
        'RU' => '/images/blipblap/RUS.svg',
        'SA' => '/images/blipblap/SAU.svg',
        'TR' => '/images/blipblap/TUR.svg',
        'US' => '/images/blipblap/USA.svg',
        'EU' => '/images/blipblap/EUR.svg',
    ];

    return EsimPlan::query()
        ->where('is_active', true)
        ->whereNotNull('country_name')
        ->get(['country_name', 'country_iso', 'retail_price', 'currency'])
        ->groupBy('country_name')
        ->map(function ($plans, string $country) use ($flagMap): array {
            $iso = strtoupper((string) $plans->pluck('country_iso')->filter()->first());
            $price = $plans->pluck('retail_price')->filter(fn ($value) => (float) $value > 0)->min();

            return [
                'name' => $country,
                'iso' => $iso ?: strtoupper(substr($country, 0, 2)),
                'flag_url' => $flagMap[$iso] ?? (strlen($iso) === 2 ? 'https://flagcdn.com/w80/' . strtolower($iso) . '.png' : ''),
                'plan_count' => $plans->count(),
                'min_price' => $price ? (float) $price : null,
                'currency' => $plans->pluck('currency')->filter()->first() ?: config('blipblap.currency', 'USD'),
                'url' => '/destinations/' . Str::slug($country),
            ];
        })
        ->sortBy('name')
        ->values();
})->name('destinations.list');
Route::get('/destinations/{slug}', [DestinationController::class, 'show'])->name('destinations.show');
Route::get('/united-arab-emirates-esim', [DestinationController::class, 'show'])->defaults('slug', 'united-arab-emirates')->name('country.uae');
Route::get('/plans/{plan:slug}', [PlanController::class, 'show'])->name('plans.show');
Route::get('/checkout/{plan:slug}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('/checkout/{plan:slug}/payment', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::post('/checkout/{plan:slug}/stripe', [CheckoutController::class, 'stripe'])->name('checkout.stripe');
Route::get('/checkout/{plan:slug}/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');
Route::post('/support/chat', SupportChatController::class)->name('support.chat');
Route::get('/pages/{page:slug}', [ContentPageController::class, 'show'])->name('pages.show');

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
    Route::get('/my-esims', MyEsimController::class)->name('my-esims');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
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
    Route::post('/plans/bulk-update', [AdminPlanController::class, 'bulkUpdate'])->name('plans.bulk-update');
    Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
    Route::get('/promotions', [AdminPromotionController::class, 'index'])->name('promotions.index');
    Route::post('/promotions', [AdminPromotionController::class, 'store'])->name('promotions.store');
    Route::patch('/promotions/{promotion}', [AdminPromotionController::class, 'update'])->name('promotions.update');
    Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
    Route::patch('/content/homepage', [AdminContentController::class, 'updateHomepage'])->name('content.homepage');
    Route::patch('/content/email', [AdminContentController::class, 'updateEmail'])->name('content.email');
    Route::post('/content/pages', [AdminContentController::class, 'storePage'])->name('content.pages.store');
    Route::patch('/content/pages/{page}', [AdminContentController::class, 'updatePage'])->name('content.pages.update');
    Route::delete('/content/pages/{page}', [AdminContentController::class, 'destroyPage'])->name('content.pages.destroy');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::patch('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'sendResetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

Route::get('/{country}-esim', [DestinationController::class, 'show'])->name('country.esim');
