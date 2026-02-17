<?php

use App\Http\Controllers\Tenant\VendorController;
use App\Http\Controllers\Tenant\ItemController;
use App\Http\Controllers\Tenant\ItemCategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Procurement Routes
|--------------------------------------------------------------------------
|
| These routes are for the procurement module within tenant subdomains.
| Uses standard 'web' guard with auth middleware.
|
*/

Route::middleware(['auth'])->prefix('procurement')->name('tenant.')->group(function () {
    
    // Vendors
    Route::resource('vendors', VendorController::class);
    Route::post('vendors/{vendor}/blacklist', [VendorController::class, 'blacklist'])->name('vendors.blacklist');
    Route::post('vendors/{vendor}/activate', [VendorController::class, 'activate'])->name('vendors.activate');
    Route::get('vendors-export', [VendorController::class, 'export'])->name('vendors.export');
    
    // Items
    Route::resource('items', ItemController::class);
    Route::get('items-search', [ItemController::class, 'search'])->name('items.search');
    Route::post('items/{item}/update-stock', [ItemController::class, 'updateStock'])->name('items.update-stock');
    Route::post('items-import', [ItemController::class, 'import'])->name('items.import');
    Route::get('items-export', [ItemController::class, 'export'])->name('items.export');
    
    // Item Categories
    Route::get('categories', [ItemCategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [ItemCategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [ItemCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [ItemCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('categories-list', [ItemCategoryController::class, 'list'])->name('categories.list');
});