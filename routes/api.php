<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\VersionController;
use App\Http\Middleware\AttachRequestId;
use Illuminate\Support\Facades\Route;

Route::middleware(AttachRequestId::class)->group(function () {
    Route::get('/version', VersionController::class)->name('version');
    Route::get('/health', HealthController::class)->name('health');
});
