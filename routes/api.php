<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\VersionController;
use Illuminate\Support\Facades\Route;

Route::get('/version', VersionController::class)->name('version');
Route::get('/health', HealthController::class)->name('health');
