<?php

use App\Http\Controllers\Tenant\PurchaseRequisitionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes - Purchase Requisitions
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('procurement')->name('tenant.')->group(function () {
    
    // Purchase Requisitions
    Route::prefix('requisitions')->name('requisitions.')->group(function () {
        // List
        Route::get('/', [PurchaseRequisitionController::class, 'index'])
            ->name('index');
        
        // Create
        Route::get('/create', [PurchaseRequisitionController::class, 'create'])
            ->name('create');
        
        Route::post('/', [PurchaseRequisitionController::class, 'store'])
            ->name('store');
        
        // View
        Route::get('/{requisition}', [PurchaseRequisitionController::class, 'show'])
            ->name('show');
        
        // Edit
        Route::get('/{requisition}/edit', [PurchaseRequisitionController::class, 'edit'])
            ->name('edit');
        
        Route::put('/{requisition}', [PurchaseRequisitionController::class, 'update'])
            ->name('update');
        
        // Delete attachment
        Route::delete('/{requisition}/attachments/{index}', [PurchaseRequisitionController::class, 'deleteAttachment'])
            ->name('attachments.delete');
        
        // Auto-save draft
        Route::post('/autosave', [PurchaseRequisitionController::class, 'autosave'])
            ->name('autosave');
        
        // Search items for autocomplete
        Route::get('/search/items', [PurchaseRequisitionController::class, 'searchItems'])
            ->name('search.items');
    });
});