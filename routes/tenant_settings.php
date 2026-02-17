<?php
// Add these inside your tenant auth middleware group in routes/web.php

use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\BranchController;

Route::middleware(['auth'])->prefix('settings')->name('tenant.settings.')->group(function () {
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('branches',    BranchController::class)->except(['show']);
});