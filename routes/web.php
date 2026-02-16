<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantController;
use Inertia\Inertia;

// Include authentication routes (login, logout, etc.)
require __DIR__.'/auth.php';
require __DIR__.'/procurement.php';

/*
|--------------------------------------------------------------------------
| Marketing Website Routes (Public)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Marketing/Home');
})->name('home');

Route::get('/features', function () {
    return Inertia::render('Marketing/Features');
})->name('features');

Route::get('/pricing', function () {
    return Inertia::render('Marketing/Pricing');
})->name('pricing');

Route::get('/about', function () {
    return Inertia::render('Marketing/About');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('Marketing/Contact');
})->name('contact');

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (Protected)
|--------------------------------------------------------------------------
| Subdomain: admin.easyschool.local
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth']) // Add auth middleware for protection
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        
        // Tenant Management
        Route::resource('tenants', TenantController::class);
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])
            ->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])
            ->name('tenants.activate');
            
        Route::post('/tenants/{tenant}/users', [TenantController::class, 'storeUser'])
            ->name('tenants.users.store');
        // Future: Subscriptions, Payments, Modules, etc.
    });

/*
|--------------------------------------------------------------------------
| Tenant Application Routes (Protected)
|--------------------------------------------------------------------------
| Subdomains: junior.easyschool.local, middle.easyschool.local, etc.
*/

Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Tenant/Dashboard');
    })->name('dashboard');
    
    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread', [NotificationController::class, 'getUnread'])->name('unread');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });
    
    // Procurement Routes (Week 4)
    Route::prefix('procurement')->name('procurement.')->group(function () {
        // Vendors
        // Items
        // Purchase Requisitions
        // Purchase Orders
        // Goods Receipt Notes
    });
    
    // Assets Routes (Week 8+)
    Route::prefix('assets')->name('assets.')->group(function () {
        // Asset Register
        // Categories
        // Transfers
        // Maintenance
        // Depreciation
    });
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        // Procurement Reports
        // Asset Reports
    });
    
    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        // School Settings
        // User Management
        // Branch Management
    });
    
});

/*
|--------------------------------------------------------------------------
| Test Routes (Development Only - Remove in Production)
|--------------------------------------------------------------------------
*/

if (app()->environment('local')) {
    Route::get('/test-toast', function () {
        return redirect('/dashboard')->with('success', 'Toast is working! 🎉');
    })->middleware('auth')->name('test.toast');
    
    Route::get('/test-error', function () {
        return redirect('/dashboard')->with('error', 'Error toast test!');
    })->middleware('auth')->name('test.error');
    
    Route::get('/test-warning', function () {
        return redirect('/dashboard')->with('warning', 'Warning toast test!');
    })->middleware('auth')->name('test.warning');
    
    Route::get('/test-info', function () {
        return redirect('/dashboard')->with('info', 'Info toast test!');
    })->middleware('auth')->name('test.info');
}