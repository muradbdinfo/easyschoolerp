<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Controllers - Admin
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Tenant\TenantDashboardController;

// Controllers - Tenant (Notifications)
use App\Http\Controllers\NotificationController;

// Controllers - Tenant (Procurement)
use App\Http\Controllers\Tenant\VendorController;
use App\Http\Controllers\Tenant\ItemController;
use App\Http\Controllers\Tenant\ItemCategoryController;
use App\Http\Controllers\Tenant\PurchaseRequisitionController;
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\GoodsReceiptNoteController;
use App\Http\Controllers\Tenant\StockIssueController;

// Controllers - Tenant (Assets)
use App\Http\Controllers\Tenant\AssetCategoryController;
use App\Http\Controllers\Tenant\AssetController;
use App\Http\Controllers\Tenant\AssetTransferController;
use App\Http\Controllers\Tenant\AssetMaintenanceController;
use App\Http\Controllers\Tenant\AssetDepreciationController;
use App\Http\Controllers\Tenant\AssetVerificationController;

// Controllers - Tenant (Reports & Settings)
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\SchoolSettingsController;
use App\Http\Controllers\Tenant\ApprovalPolicyController;

// Auth routes
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Marketing Routes (Public — no auth)
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
| Tenant Routes  (all protected by auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');

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
        Route::get('categories',               [ItemCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories',              [ItemCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}',    [ItemCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [ItemCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('categories-list',          [ItemCategoryController::class, 'list'])->name('categories.list');

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
            Route::get('/{requisition}/items-json',             [PurchaseRequisitionController::class, 'itemsJson'])->name('items-json');
        });

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/send',   [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::get('purchase-orders-approved-prs',            [PurchaseOrderController::class, 'approvedPRs'])->name('purchase-orders.approved-prs');

        // Goods Receipt Notes
        Route::resource('grn', GoodsReceiptNoteController::class)
            ->only(['index', 'create', 'store', 'show']);

            // Stock Issues
Route::prefix('stock-issues')->name('stock-issues.')->group(function () {
    Route::get('/',                              [StockIssueController::class, 'index'])->name('index');
    Route::get('/create',                        [StockIssueController::class, 'create'])->name('create');
    Route::post('/',                             [StockIssueController::class, 'store'])->name('store');
    Route::get('/{stockIssue}',                  [StockIssueController::class, 'show'])->name('show');
    Route::post('/{stockIssue}/issue',           [StockIssueController::class, 'issue'])->name('issue');
    Route::post('/{stockIssue}/cancel',          [StockIssueController::class, 'cancel'])->name('cancel');
});

// Stock Ledger (per item history)
Route::get('stock-ledger', [StockIssueController::class, 'ledger'])->name('stock-ledger');

    });

    // -- Assets --------------------------------------------------------------
    Route::prefix('assets')->name('tenant.assets.')->group(function () {

        // Asset Register
        Route::get('/',       [AssetController::class, 'index'])->name('index');
        Route::get('/create', [AssetController::class, 'create'])->name('create');
        Route::post('/',      [AssetController::class, 'store'])->name('store');

        // Asset Categories
        Route::get('/categories',                    [AssetCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories',                   [AssetCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{assetCategory}',    [AssetCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{assetCategory}', [AssetCategoryController::class, 'destroy'])->name('categories.destroy');

        // Transfers
        Route::get('/transfers',                          [AssetTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create',                   [AssetTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers',                         [AssetTransferController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/{assetTransfer}',          [AssetTransferController::class, 'show'])->name('transfers.show');
        Route::post('/transfers/{assetTransfer}/approve', [AssetTransferController::class, 'approve'])->name('transfers.approve');
        Route::post('/transfers/{assetTransfer}/reject',  [AssetTransferController::class, 'reject'])->name('transfers.reject');

        // Maintenance
        Route::get('/maintenance',                              [AssetMaintenanceController::class, 'index'])->name('maintenance.index');
        Route::get('/maintenance/create',                       [AssetMaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('/maintenance',                             [AssetMaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/maintenance/{assetMaintenance}',           [AssetMaintenanceController::class, 'show'])->name('maintenance.show');
        Route::get('/maintenance/{assetMaintenance}/edit',      [AssetMaintenanceController::class, 'edit'])->name('maintenance.edit');
        Route::put('/maintenance/{assetMaintenance}',           [AssetMaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('/maintenance/{assetMaintenance}',        [AssetMaintenanceController::class, 'destroy'])->name('maintenance.destroy');
        Route::post('/maintenance/{assetMaintenance}/complete', [AssetMaintenanceController::class, 'complete'])->name('maintenance.complete');

        // Depreciation
        Route::get('/depreciation',                  [AssetDepreciationController::class, 'index'])->name('depreciation.index');
        Route::get('/depreciation/run',              [AssetDepreciationController::class, 'run'])->name('depreciation.run');
        Route::post('/depreciation/process',         [AssetDepreciationController::class, 'process'])->name('depreciation.process');
        Route::get('/depreciation/{asset}/schedule', [AssetDepreciationController::class, 'schedule'])->name('depreciation.schedule');

        // Physical Verification
        Route::get('/verification',                              [AssetVerificationController::class, 'index'])->name('verification.index');
        Route::get('/verification/create',                       [AssetVerificationController::class, 'create'])->name('verification.create');
        Route::post('/verification',                             [AssetVerificationController::class, 'store'])->name('verification.store');
        Route::get('/verification/{cycle}',                      [AssetVerificationController::class, 'show'])->name('verification.show');
        Route::post('/verification/{cycle}/complete',            [AssetVerificationController::class, 'complete'])->name('verification.complete');
        Route::post('/verification/{cycle}/items/{item}/verify', [AssetVerificationController::class, 'verify'])->name('verification.verify');
        Route::post('/verification/items/{item}/resolve',        [AssetVerificationController::class, 'resolveDiscrepancy'])->name('verification.resolve');

        // Asset wildcard routes LAST (after all static prefixes)
        Route::get('/{asset}',      [AssetController::class, 'show'])->name('show');
        Route::get('/{asset}/edit', [AssetController::class, 'edit'])->name('edit');
        Route::put('/{asset}',      [AssetController::class, 'update'])->name('update');
        Route::delete('/{asset}',   [AssetController::class, 'destroy'])->name('destroy');

        // Asset QR code
        Route::get('/{asset}/qr', [AssetController::class, 'generateQR'])->name('qr');

        // Asset photos
        Route::post('/{asset}/photos',   [AssetController::class, 'uploadPhoto'])->name('photos.upload');
        Route::delete('/{asset}/photos', [AssetController::class, 'deletePhoto'])->name('photos.delete');

    });

    // -- Reports -------------------------------------------------------------
    Route::prefix('reports')->name('tenant.reports.')->group(function () {
        Route::get('/procurement',     [ReportController::class, 'procurement'])->name('procurement');
        Route::get('/assets',          [ReportController::class, 'assets'])->name('assets');
        Route::get('stock',           [ReportController::class, 'stock'])->name('stock');
        Route::get('/dashboard-stats', [ReportController::class, 'dashboardStats'])->name('dashboard-stats');
    });

    // -- Settings ------------------------------------------------------------
    Route::prefix('settings')->name('tenant.settings.')->group(function () {

        // Departments & Branches
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('branches',    BranchController::class)->except(['show']);

        // User Management
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('users.toggle-active');

        // School Settings
        Route::get('school',              [SchoolSettingsController::class, 'index'])->name('school.index');
        Route::post('school',             [SchoolSettingsController::class, 'update'])->name('school.update');
        Route::post('school/remove-logo', [SchoolSettingsController::class, 'removeLogo'])->name('school.remove-logo');
        Route::post('school/modules',     [SchoolSettingsController::class, 'updateModules'])->name('school.modules');

        // Approval Policies
        Route::prefix('approval-policies')->name('approval-policies.')->group(function () {
            Route::get('/',                    [ApprovalPolicyController::class, 'index'])->name('index');
            Route::post('/',                   [ApprovalPolicyController::class, 'store'])->name('store');
            Route::put('/{approvalPolicy}',    [ApprovalPolicyController::class, 'update'])->name('update');
            Route::delete('/{approvalPolicy}', [ApprovalPolicyController::class, 'destroy'])->name('destroy');
            Route::post('/copy-defaults',      [ApprovalPolicyController::class, 'copyDefaults'])->name('copy-defaults');
            Route::post('/reset',              [ApprovalPolicyController::class, 'resetToDefaults'])->name('reset');
        });

    });

}); // end middleware(['auth'])