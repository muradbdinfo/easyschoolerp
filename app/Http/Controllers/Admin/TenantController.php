<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::query();

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('subdomain', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        $tenants = $query->withCount('users')
            ->latest()
            ->paginate(15);

        return inertia('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status', 'plan']),
        ]);
    }

    public function create()
    {
        return inertia('Admin/Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:63|unique:tenants,subdomain|alpha_dash',
            'plan' => 'required|in:basic,professional,enterprise',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'trial_days' => 'required|integer|min:0|max:90',
        ]);

        try {
            DB::beginTransaction();

            // Create tenant
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'subdomain' => strtolower($validated['subdomain']),
                'database_name' => 'tenant_' . strtolower($validated['subdomain']),
                'status' => 'trial',
                'plan' => $validated['plan'],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'trial_ends_at' => now()->addDays($validated['trial_days']),
                'active_modules' => ['procurement', 'assets'], // Default modules
            ]);

            // TODO: Create tenant database
            // TODO: Run migrations on tenant database
            // TODO: Seed initial data
            // TODO: Send welcome email

            DB::commit();

            return redirect()
                ->route('admin.tenants.index')
                ->with('success', 'Tenant created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create tenant: ' . $e->getMessage()]);
        }
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('users');
        
        return inertia('Admin/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return inertia('Admin/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'required|in:basic,professional,enterprise',
            'status' => 'required|in:trial,active,suspended,cancelled',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'mrr' => 'required|numeric|min:0',
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant updated successfully!');
    }

    public function destroy(Tenant $tenant)
    {
        // Soft delete
        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully!');
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->suspend();

        return back()->with('success', 'Tenant suspended successfully!');
    }

    public function activate(Tenant $tenant)
    {
        $tenant->activate();

        return back()->with('success', 'Tenant activated successfully!');
    }
}