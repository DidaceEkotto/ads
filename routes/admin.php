<?php

use App\Livewire\Admin\DashboardOverview;
use App\Livewire\Admin\FraudMonitor;
use App\Livewire\Admin\PayoutManager;
use App\Livewire\Admin\PlatformSettings;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\ValidationQueue;
use Illuminate\Support\Facades\Route;

// Module Administration : uniquement Laravel Livewire (hors Inertia)
Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
Route::get('/validations', ValidationQueue::class)->name('validations');
Route::get('/payouts', PayoutManager::class)->name('payouts');
Route::get('/fraud', FraudMonitor::class)->name('fraud');
Route::get('/users', UserManagement::class)->name('users');
Route::get('/settings', PlatformSettings::class)->name('settings');
