<?php

use App\Http\Controllers\Advertiser\CampaignController;
use App\Http\Controllers\Advertiser\DashboardController;
use App\Http\Controllers\Advertiser\ProfileController;
use App\Http\Controllers\Advertiser\SettingsController;
use App\Http\Controllers\Advertiser\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class)->name('dashboard');

Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::post('/deposit', [WalletController::class, 'deposit'])->middleware('throttle:10,1')->name('deposit');
    Route::get('/callback', [WalletController::class, 'callback'])->name('callback');
    Route::get('/transactions/{transaction}/invoice', [WalletController::class, 'invoice'])->name('invoice');
});

Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::get('/create', [CampaignController::class, 'create'])->name('create');
    Route::post('/', [CampaignController::class, 'store'])->name('store');
    Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
    Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
    Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
    Route::patch('/{campaign}/toggle', [CampaignController::class, 'toggleStatus'])->name('toggle');
    Route::patch('/{campaign}/submit', [CampaignController::class, 'submitForReview'])->name('submit');
});

Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::get('/password', [ProfileController::class, 'editPassword'])->name('password.edit');
Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
