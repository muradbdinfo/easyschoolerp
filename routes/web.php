<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Controllers - Admin
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;

// Controllers - Tenant
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Tenant\VendorController;
use App\Http\Controllers\Tenant\ItemController;
use App\Http\Controllers\Tenant\ItemCategoryController;
use App\Http\Controllers\Tenant\PurchaseRequisitionController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\BranchController;

// Auth routes
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Marketing Routes (Public)
|--------------------------------------------------------------------------
*/

Route::get('/',        fn () => Inertia::render('Marketing/Home'))->name('home');
Route::get('/features',fn () => Inertia::render('Marketing/Features'))->name('features');
Route::get('/pricing', fn () => Inertia::render('Marketing/Pricing'))->name('pricing');
Route::get('/about',   fn () => Inertia::render('Marketing/About'))->name('about');
Route::get('/contact', fn () => Inertia::render('Marketing/Contact'))->name('contact');

/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('tenants', TenantController::class);
    Route::post('/tenants/{tenant}/suspend',  [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('/tenants/{tenant}/users',    [TenantController::class, 'storeUser'])->name('tenants.users.store');

});

/*
|--------------------------------------------------------------------------
| Tenant Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', fn () => Inertia::render('Tenant/Dashboard'))->name('dashboard');

    /*
    | Notifications
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                           [NotificationController::class, 'index'])->name('index');
        Route::get('/unread',                     [NotificationController::class, 'getUnread'])->name('unread');
        Route::post('/{notification}/read',       [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read',             [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}',          [NotificationController::class, 'destroy'])->name('destroy');
    });

    /*
    | Procurement
    */
    Route::prefix('procurement')->name('tenant.')->group(function () {

        // Vendors
        Route::resource('vendors', VendorController::class);
        Route::post('vendors/{vendor}/blacklist', [VendorController::class, 'blacklist'])->name('vendors.blacklist');
        Route::post('vendors/{vendor}/activate',  [VendorController::class, 'activate'])->name('vendors.activate');
        Route::get('vendors-export',              [VendorController::class, 'export'])->name('vendors.export');

        // Items
        Route::resource('items', ItemController::class);
        Route::get('items-search',               [ItemController::class, 'search'])->name('items.search');
        Route::post('items/{item}/update-stock', [ItemController::class, 'updateStock'])->name('items.update-stock');
        Route::post('items-import',              [ItemController::class, 'import'])->name('items.import');
        Route::get('items-export',               [ItemController::class, 'export'])->name('items.export');

        // Item Categories
        Route::get('categories',                       [ItemCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories',                      [ItemCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}',            [ItemCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}',         [ItemCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('categories-list',                  [ItemCategoryController::class, 'list'])->name('categories.list');

        // Purchase Requisitions
        Route::prefix('requisitions')->name('requisitions.')->group(function () {
            Route::get('/',                                        [PurchaseRequisitionController::class, 'index'])->name('index');
            Route::get('/create',                                  [PurchaseRequisitionController::class, 'create'])->name('create');
            Route::post('/',                                       [PurchaseRequisitionController::class, 'store'])->name('store');
            Route::get('/{requisition}',                           [PurchaseRequisitionController::class, 'show'])->name('show');
            Route::get('/{requisition}/edit',                      [PurchaseRequisitionController::class, 'edit'])->name('edit');
            Route::put('/{requisition}',                           [PurchaseRequisitionController::class, 'update'])->name('update');
            Route::delete('/{requisition}/attachments/{index}',    [PurchaseRequisitionController::class, 'deleteAttachment'])->name('attachments.delete');
            Route::post('/autosave',                               [PurchaseRequisitionController::class, 'autosave'])->name('autosave');
            Route::get('/search/items',                            [PurchaseRequisitionController::class, 'searchItems'])->name('search.items');
        });

    });

    /*
    | Settings
    */
    Route::prefix('settings')->name('tenant.settings.')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('branches',    BranchController::class)->except(['show']);
    });

    /*
    | Assets (Week 8+)
    */
    Route::prefix('assets')->name('assets.')->group(function () {
        // Asset Register, Categories, Transfers, Maintenance, Depreciation
    });

    /*
    | Reports
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        // Procurement Reports, Asset Reports
    });

});