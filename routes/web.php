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
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\GoodsReceiptNoteController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\AssetCategoryController;
use App\Http\Controllers\Tenant\AssetController;
use App\Http\Controllers\Tenant\AssetTransferController;
use App\Http\Controllers\Tenant\AssetMaintenanceController;
use App\Http\Controllers\Tenant\AssetDepreciationController;
use App\Http\Controllers\Tenant\ReportController;

// Auth routes
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Marketing Routes (Public)
|--------------------------------------------------------------------------
*/
Route::get('/',         fn () => Inertia::render('Marketing/Home'))->name('home');
Route::get('/features', fn () => Inertia::render('Marketing/Features'))->name('features');
Route::get('/pricing',  fn () => Inertia::render('Marketing/Pricing'))->name('pricing');
Route::get('/about',    fn () => Inertia::render('Marketing/About'))->name('about');
Route::get('/contact',  fn () => Inertia::render('Marketing/Contact'))->name('contact');

/*
|--------------------------------------------------------------------------
| Admin Routes
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
| Tenant Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', fn () => Inertia::render('Tenant/Dashboard'))->name('dashboard');

    // -- Notifications -------------------------------------------------------
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                     [NotificationController::class, 'index'])->name('index');
        Route::get('/unread',               [NotificationController::class, 'getUnread'])->name('unread');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read',       [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}',    [NotificationController::class, 'destroy'])->name('destroy');
    });

    // -- Procurement ---------------------------------------------------------
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
        Route::get('categories',              [ItemCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories',             [ItemCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}',   [ItemCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}',[ItemCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('categories-list',         [ItemCategoryController::class, 'list'])->name('categories.list');

        // Purchase Requisitions
        Route::prefix('requisitions')->name('requisitions.')->group(function () {
            Route::get('/',             [PurchaseRequisitionController::class, 'index'])->name('index');
            Route::get('/create',       [PurchaseRequisitionController::class, 'create'])->name('create');
            Route::post('/',            [PurchaseRequisitionController::class, 'store'])->name('store');
            Route::post('/autosave',    [PurchaseRequisitionController::class, 'autosave'])->name('autosave');
            Route::get('/search/items', [PurchaseRequisitionController::class, 'searchItems'])->name('search.items');
            Route::get('/{requisition}',                        [PurchaseRequisitionController::class, 'show'])->name('show');
            Route::get('/{requisition}/edit',                   [PurchaseRequisitionController::class, 'edit'])->name('edit');
            Route::put('/{requisition}',                        [PurchaseRequisitionController::class, 'update'])->name('update');
            Route::delete('/{requisition}/attachments/{index}', [PurchaseRequisitionController::class, 'deleteAttachment'])->name('attachments.delete');
            Route::post('/{requisition}/approve',               [PurchaseRequisitionController::class, 'approve'])->name('approve');
            Route::post('/{requisition}/reject',                [PurchaseRequisitionController::class, 'reject'])->name('reject');
        });

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/send',   [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::get('purchase-orders-approved-prs',            [PurchaseOrderController::class, 'approvedPRs'])->name('purchase-orders.approved-prs');

        // Goods Receipt Notes
        Route::resource('grn', GoodsReceiptNoteController::class)
            ->only(['index', 'create', 'store', 'show']);

    });

    // -- Settings ------------------------------------------------------------
    Route::prefix('settings')->name('tenant.settings.')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('branches',    BranchController::class)->except(['show']);
    });

    // -- Assets --------------------------------------------------------------
    // IMPORTANT: all static sub-resource prefixes (categories, transfers,
    // maintenance, depreciation) MUST be declared before the /{asset} wildcard
    // routes, otherwise Laravel will treat e.g. "maintenance" as an asset ID.
    Route::prefix('assets')->name('tenant.assets.')->group(function () {

        // ── Asset Register (static routes first) ──────────────────────────
        Route::get('/',       [AssetController::class, 'index'])->name('index');
        Route::get('/create', [AssetController::class, 'create'])->name('create');
        Route::post('/',      [AssetController::class, 'store'])->name('store');

        // ── Categories ────────────────────────────────────────────────────
        Route::get('/categories',                    [AssetCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories',                   [AssetCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{assetCategory}',    [AssetCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{assetCategory}', [AssetCategoryController::class, 'destroy'])->name('categories.destroy');

        // ── Transfers ─────────────────────────────────────────────────────
        Route::get('/transfers',                          [AssetTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create',                   [AssetTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers',                         [AssetTransferController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/{assetTransfer}',          [AssetTransferController::class, 'show'])->name('transfers.show');
        Route::post('/transfers/{assetTransfer}/approve', [AssetTransferController::class, 'approve'])->name('transfers.approve');
        Route::post('/transfers/{assetTransfer}/reject',  [AssetTransferController::class, 'reject'])->name('transfers.reject');

        // ── Maintenance ───────────────────────────────────────────────────
        Route::get('/maintenance',                              [AssetMaintenanceController::class, 'index'])->name('maintenance.index');
        Route::get('/maintenance/create',                       [AssetMaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('/maintenance',                             [AssetMaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/maintenance/{assetMaintenance}',           [AssetMaintenanceController::class, 'show'])->name('maintenance.show');
        Route::get('/maintenance/{assetMaintenance}/edit',      [AssetMaintenanceController::class, 'edit'])->name('maintenance.edit');
        Route::put('/maintenance/{assetMaintenance}',           [AssetMaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('/maintenance/{assetMaintenance}',        [AssetMaintenanceController::class, 'destroy'])->name('maintenance.destroy');
        Route::post('/maintenance/{assetMaintenance}/complete', [AssetMaintenanceController::class, 'complete'])->name('maintenance.complete');

        // ── Depreciation ──────────────────────────────────────────────────
        Route::get('/depreciation',                  [AssetDepreciationController::class, 'index'])->name('depreciation.index');
        Route::get('/depreciation/run',              [AssetDepreciationController::class, 'run'])->name('depreciation.run');
        Route::post('/depreciation/process',         [AssetDepreciationController::class, 'process'])->name('depreciation.process');
        Route::get('/depreciation/{asset}/schedule', [AssetDepreciationController::class, 'schedule'])->name('depreciation.schedule');

        // ── Asset wildcard routes LAST (so they never swallow the above) ──
        Route::get('/{asset}',      [AssetController::class, 'show'])->name('show');
        Route::get('/{asset}/edit', [AssetController::class, 'edit'])->name('edit');
        Route::put('/{asset}',      [AssetController::class, 'update'])->name('update');
        Route::delete('/{asset}',   [AssetController::class, 'destroy'])->name('destroy');

        // Photos (also after static routes to be safe)
        Route::post('/{asset}/photos',   [AssetController::class, 'uploadPhoto'])->name('photos.upload');
        Route::delete('/{asset}/photos', [AssetController::class, 'deletePhoto'])->name('photos.delete');

    });

    // -- Reports -------------------------------------------------------------
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('procurement', [ReportController::class, 'procurement'])->name('procurement');
        Route::get('assets',      [ReportController::class, 'assets'])->name('assets');
    });

});