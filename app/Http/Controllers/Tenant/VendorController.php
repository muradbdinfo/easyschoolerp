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
        $query = Vendor::query()->with(['creator', 'updater']);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
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
            ->withQueryString();

        return Inertia::render('Tenant/Procurement/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'type', 'status', 'rating_min']),
            'stats' => [
                'total' => Vendor::count(),
                'active' => Vendor::active()->count(),
                'blacklisted' => Vendor::blacklisted()->count(),
                'suppliers' => Vendor::byType('supplier')->count(),
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

        // Create notification
        $this->createNotification(
            'Vendor Created',
            "New vendor {$vendor->name} ({$vendor->code}) has been created.",
            'vendor_created',
            $vendor
        );

        return redirect()
            ->route('tenant.vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor): Response
    {
        $vendor->load(['creator', 'updater', 'purchaseOrders' => function($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('Tenant/Procurement/Vendors/Show', [
            'vendor' => $vendor,
            'stats' => [
                'total_orders' => $vendor->purchaseOrders()->count(),
                'total_amount' => $vendor->purchaseOrders()->sum('total_amount'),
                'pending_orders' => $vendor->purchaseOrders()->where('status', 'pending')->count(),
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
        // Check if vendor has any purchase orders
        if ($vendor->purchaseOrders()->exists()) {
            return back()->with('error', 'Cannot delete vendor with existing purchase orders.');
        }

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

        $vendor->blacklist($request->reason);

        $this->createNotification(
            'Vendor Blacklisted',
            "Vendor {$vendor->name} has been blacklisted. Reason: {$request->reason}",
            'vendor_blacklisted',
            $vendor
        );

        return back()->with('success', 'Vendor has been blacklisted.');
    }

    /**
     * Activate a blacklisted vendor.
     */
    public function activate(Vendor $vendor): RedirectResponse
    {
        $vendor->activate();

        return back()->with('success', 'Vendor has been activated.');
    }

    /**
     * Export vendors to Excel.
     */
    public function export(Request $request)
    {
        // This will be implemented with Maatwebsite\Excel
        // For now, return JSON
        $query = Vendor::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->get();

        return response()->json($vendors);
    }

    /**
     * Helper method to create notifications
     */
    private function createNotification(string $title, string $message, string $type, $related = null): void
    {
        // NotificationService will be implemented in Week 3
        // For now, this is a placeholder
    }
}