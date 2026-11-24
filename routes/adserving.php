<?php

use App\Http\Controllers\Public\AdBlockMetricController;
use App\Http\Controllers\Public\AdServeController;
use App\Http\Controllers\Public\AutoAdServeController;
use App\Http\Controllers\Public\ClickTrackingController;
use App\Http\Controllers\Public\ConversionPixelController;
use App\Http\Controllers\Public\SocialRedirectController;
use App\Http\Controllers\Public\ViewTrackingController;
use Illuminate\Support\Facades\Route;

// Endpoints publics de diffusion et suivi (sans session Inertia, noms neutres anti-bloqueur)
Route::get('/a/{siteToken}.js', [AutoAdServeController::class, 'script'])
    ->where('siteToken', '[A-Za-z0-9]+')
    ->name('adserving.auto');

Route::get('/d/{token}.js', [AdServeController::class, 'script'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('adserving.script');

Route::get('/d/frame/{token}', [AdServeController::class, 'frame'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('adserving.frame');

Route::get('/click/{token}', [ClickTrackingController::class, 'track'])
    ->name('adserving.click');

Route::get('/c/{code}', [SocialRedirectController::class, 'redirect'])
    ->where('code', '[A-Za-z0-9]+')
    ->name('adserving.social');

Route::post('/m/ab', [AdBlockMetricController::class, 'record'])
    ->name('adserving.adblock');

Route::match(['get', 'post'], '/m/v', [ViewTrackingController::class, 'record'])
    ->name('adserving.view');

Route::get('/m/conv/{campaign}', [ConversionPixelController::class, 'track'])
    ->whereNumber('campaign')
    ->name('adserving.conversion');
