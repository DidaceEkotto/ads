<?php

use App\Http\Controllers\Publisher\DashboardController;
use App\Http\Controllers\Publisher\PaymentMethodController;
use App\Http\Controllers\Publisher\PayoutController;
use App\Http\Controllers\Publisher\ProfileController;
use App\Http\Controllers\Publisher\SiteController;
use App\Http\Controllers\Publisher\SocialController;
use App\Http\Controllers\Publisher\SocialOAuthController;
use App\Http\Controllers\Publisher\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/stats', StatsController::class)->name('stats');

Route::prefix('sites')->name('sites.')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('index');
    Route::post('/', [SiteController::class, 'store'])->name('store');
    Route::get('/{site}', [SiteController::class, 'show'])->name('show');
    Route::patch('/{site}/auto-ads', [SiteController::class, 'updateAutoAds'])->name('auto-ads');
});

Route::prefix('social')->name('social.')->group(function () {
    Route::get('/', [SocialController::class, 'index'])->name('index');
    Route::post('/accounts', [SocialController::class, 'storeAccount'])->name('accounts.store');
    Route::get('/oauth/{provider}/redirect', [SocialOAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/{provider}/callback', [SocialOAuthController::class, 'callback'])->name('oauth.callback');
    Route::post('/campaigns/{campaign}/join', [SocialController::class, 'joinCampaign'])->name('campaigns.join');
    Route::post('/placements/{placement}/proof', [SocialController::class, 'submitProof'])->name('placements.proof');
});

Route::prefix('payouts')->name('payouts.')->group(function () {
    Route::get('/', [PayoutController::class, 'index'])->name('index');
    Route::post('/', [PayoutController::class, 'store'])->name('store');
    Route::get('/{payout}/receipt', [PayoutController::class, 'receipt'])->name('receipt');
});

Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
    Route::patch('/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])->name('default');
    Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
});

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/password', [ProfileController::class, 'editPassword'])->name('password.edit');
Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
