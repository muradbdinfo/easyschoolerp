<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors.
     */
    public function index(Request $request): Response
    {
        $query = Vendor::query();

        // Load relationships if they exist
        if (method_exists(Vendor::class, 'creator')) {
            $query->with(['creator', 'updater']);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by rating
        if ($request->filled('rating_min')) {
            $query->where('rating', '>=', $request->rating_min);
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $vendors = $query->paginate($request->get('per_page', 15))
            ->withQueryString()
            ->through(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'code' => $vendor->code,
                    'name' => $vendor->name,
                    'type' => $vendor->type,
                    'type_label' => ucfirst(str_replace('_', ' ', $vendor->type)),
                    'contact_person' => $vendor->contact_person,
                    'phone' => $vendor->phone,
                    'email' => $vendor->email,
                    'rating' => $vendor->rating ?? 0,
                    'status' => $vendor->status,
                    'status_badge' => [
                        'label' => ucfirst($vendor->status),
                        'severity' => $vendor->status === 'active' ? 'success' : 
                                    ($vendor->status === 'blacklisted' ? 'danger' : 'warning')
                    ],
                    'created_at' => $vendor->created_at,
                    'updated_at' => $vendor->updated_at,
                ];
            });

        return Inertia::render('Tenant/Procurement/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'type', 'status', 'rating_min']),
            'stats' => [
                'total' => Vendor::count(),
                'active' => Vendor::where('status', 'active')->count(),
                'blacklisted' => Vendor::where('status', 'blacklisted')->count(),
                'suppliers' => Vendor::where('type', 'supplier')->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new vendor.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Procurement/Vendors/Create');
    }

    /**
     * Store a newly created vendor in storage.
     */
    public function store(VendorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $vendor = Vendor::create($data);

        return redirect()
            ->route('tenant.vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor): Response
    {
        // Load relationships if they exist
        if (method_exists($vendor, 'creator')) {
            $vendor->load(['creator', 'updater']);
        }

        return Inertia::render('Tenant/Procurement/Vendors/Show', [
            'vendor' => $vendor,
            'stats' => [
                'total_orders' => 0, // Will be implemented in Week 5
                'total_amount' => 0,
                'pending_orders' => 0,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified vendor.
     */
    public function edit(Vendor $vendor): Response
    {
        return Inertia::render('Tenant/Procurement/Vendors/Edit', [
            'vendor' => $vendor,
        ]);
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $vendor->update($data);

        return redirect()
            ->route('tenant.vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }

    /**
     * Remove the specified vendor from storage.
     */
    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendor->delete();

        return redirect()
            ->route('tenant.vendors.index')
            ->with('success', 'Vendor deleted successfully.');
    }

    /**
     * Blacklist a vendor.
     */
    public function blacklist(Request $request, Vendor $vendor): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Update vendor status
        $vendor->update([
            'status' => 'blacklisted',
            'blacklist_reason' => $request->reason,
            'blacklisted_at' => now(),
        ]);

        return back()->with('success', 'Vendor has been blacklisted.');
    }

    /**
     * Activate a blacklisted vendor.
     */
    public function activate(Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'active',
            'blacklist_reason' => null,
            'blacklisted_at' => null,
        ]);

        return back()->with('success', 'Vendor has been activated.');
    }

    /**
     * Export vendors to Excel.
     */
    public function export(Request $request)
    {
        $query = Vendor::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->get();

        return response()->json($vendors);
    }
}