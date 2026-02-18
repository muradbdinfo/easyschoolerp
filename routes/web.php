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

    // ── Notifications ──────────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                    [NotificationController::class, 'index'])->name('index');
        Route::get('/unread',              [NotificationController::class, 'getUnread'])->name('unread');
        Route::post('/{notification}/read',[NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read',      [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}',   [NotificationController::class, 'destroy'])->name('destroy');
    });

    // ── Procurement ────────────────────────────────────────────────────────────
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
        Route::get('categories',             [ItemCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories',            [ItemCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}',  [ItemCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}',[ItemCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('categories-list',        [ItemCategoryController::class, 'list'])->name('categories.list');

        // Purchase Requisitions
        Route::prefix('requisitions')->name('requisitions.')->group(function () {
            // Static routes FIRST (before {requisition} wildcard)
            Route::get('/',             [PurchaseRequisitionController::class, 'index'])->name('index');
            Route::get('/create',       [PurchaseRequisitionController::class, 'create'])->name('create');
            Route::post('/',            [PurchaseRequisitionController::class, 'store'])->name('store');
            Route::post('/autosave',    [PurchaseRequisitionController::class, 'autosave'])->name('autosave');
            Route::get('/search/items', [PurchaseRequisitionController::class, 'searchItems'])->name('search.items');
            // Wildcard routes AFTER
            Route::get('/{requisition}',                         [PurchaseRequisitionController::class, 'show'])->name('show');
            Route::get('/{requisition}/edit',                    [PurchaseRequisitionController::class, 'edit'])->name('edit');
            Route::put('/{requisition}',                         [PurchaseRequisitionController::class, 'update'])->name('update');
            Route::delete('/{requisition}/attachments/{index}',  [PurchaseRequisitionController::class, 'deleteAttachment'])->name('attachments.delete');
            Route::post('/{requisition}/approve',                [PurchaseRequisitionController::class, 'approve'])->name('approve');
            Route::post('/{requisition}/reject',                 [PurchaseRequisitionController::class, 'reject'])->name('reject');
        });

        // Purchase Orders
        // Route names: tenant.purchase-orders.index/create/store/show/edit/update/destroy
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/send',   [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::get('purchase-orders-approved-prs',            [PurchaseOrderController::class, 'approvedPRs'])->name('purchase-orders.approved-prs');

        // Goods Receipt Notes
        // Route names: tenant.grn.index / tenant.grn.create / tenant.grn.store / tenant.grn.show
        Route::resource('grn', GoodsReceiptNoteController::class)
            ->only(['index', 'create', 'store', 'show']);

    });

    // ── Settings ───────────────────────────────────────────────────────────────
    Route::prefix('settings')->name('tenant.settings.')->group(function () {
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('branches',    BranchController::class)->except(['show']);
    });

    // ── Assets (Week 8+) ───────────────────────────────────────────────────────
    Route::prefix('assets')->name('assets.')->group(function () {
        // Uncomment as each module is built:
        // Route::resource('categories', AssetCategoryController::class);
        // Route::resource('register',   AssetController::class);
        // Route::get('register/{asset}/qr', [AssetController::class, 'generateQR'])->name('register.qr');
        // Route::resource('transfers',  AssetTransferController::class);
        // Route::post('transfers/{transfer}/approve', [AssetTransferController::class, 'approve'])->name('transfers.approve');
        // Route::resource('maintenance', AssetMaintenanceController::class);
        // Route::post('maintenance/{maintenance}/complete', [AssetMaintenanceController::class, 'complete'])->name('maintenance.complete');
        // Route::get('depreciation',    [DepreciationController::class, 'index'])->name('depreciation.index');
        // Route::post('depreciation/run',[DepreciationController::class, 'run'])->name('depreciation.run');
        // Route::resource('verification',VerificationController::class);
    });

    // ── Reports (Week 11+) ─────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        // Route::get('procurement', [ReportController::class, 'procurement'])->name('procurement');
        // Route::get('assets',      [ReportController::class, 'assets'])->name('assets');
    });

});