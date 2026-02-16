<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Calculate statistics
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::active()->count(),
            'trial_tenants' => Tenant::trial()->count(),
            'suspended_tenants' => Tenant::suspended()->count(),
            'total_mrr' => Tenant::active()->sum('mrr'),
            'new_this_month' => Tenant::whereMonth('created_at', now()->month)->count(),
        ];

        // Get recent tenants
        $recentTenants = Tenant::with('users')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                    'status' => $tenant->status,
                    'plan' => $tenant->plan,
                    'mrr' => $tenant->mrr,
                    'created_at' => $tenant->created_at->format('M d, Y'),
                    'user_count' => $tenant->users->count(),
                ];
            });

        // Get monthly revenue data (last 6 months)
        $revenueData = Tenant::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(mrr) as revenue')
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return inertia('Admin/Dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'revenueData' => $revenueData,
        ]);
    }
}