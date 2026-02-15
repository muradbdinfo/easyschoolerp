<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Marketing Routes
Route::get('/', function () {
    return Inertia::render('Marketing/Home');
});

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    });
});

// Tenant Routes
Route::get('/dashboard', function () {
    return Inertia::render('Tenant/Dashboard');
});