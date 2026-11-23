<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Demo\DemoController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $dashboardUrl = $request->user()
        ? route($request->user()->role->dashboardRoute())
        : null;

    return Inertia::render('Landing', [
        'dashboardUrl' => $dashboardUrl,
    ]);
})->name('home');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

// Pages légales (conformité camerounaise)
Route::get('/terms', fn () => Inertia::render('Legal/Terms'))->name('legal.terms');
Route::get('/privacy', fn () => Inertia::render('Legal/Privacy'))->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Webhooks de paiement (sans CSRF, protégés par signature HMAC)
Route::post('/webhooks/saspay', [PaymentWebhookController::class, 'saspay'])
    ->name('webhooks.saspay');

// Module Démo / Sandbox (actif uniquement si APP_DEMO_MODE=true)
Route::middleware('demo')->prefix('demo')->name('demo.')->group(function () {
    Route::get('/', [DemoController::class, 'index'])->name('index');
    Route::post('/login/{role}', [DemoController::class, 'loginAs'])->name('login');
    Route::post('/simulate-deposit', [DemoController::class, 'simulateDeposit'])->name('simulate-deposit');
    Route::post('/simulate-fraud', [DemoController::class, 'simulateFraud'])->name('simulate-fraud');
});

Route::get('/create-symlink', function () {
    if (is_link(public_path('storage'))) {
        return 'Le lien symbolique existe déjà !';
    }

    try {
        symlink(
            storage_path('app/public'),
            public_path('storage')
        );
        return 'Lien symbolique créé avec succès !';
    } catch (\Exception $e) {
        return 'Erreur : ' . $e->getMessage();
    }
});
