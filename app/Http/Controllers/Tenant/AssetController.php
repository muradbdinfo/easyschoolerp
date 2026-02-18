<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Asset::with(['category', 'branch', 'custodian', 'vendor'])
            ->select('assets.*');

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('custodian_id')) {
            $query->where('custodian_id', $request->custodian_id);
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $assets = $query->paginate(20)->withQueryString();

        // Summary stats
        $stats = [
            'total'       => Asset::count(),
            'active'      => Asset::where('status', 'active')->count(),
            'maintenance' => Asset::where('status', 'under_maintenance')->count(),
            'total_value' => Asset::sum('net_book_value'),
        ];

        return Inertia::render('Tenant/Assets/Register/Index', [
            'assets'     => $assets,
            'stats'      => $stats,
            'categories' => AssetCategory::active()->orderBy('name')->get(['id', 'name', 'code']),
            'branches'   => Branch::active()->orderBy('name')->get(['id', 'name']),
            'filters'    => $request->only(['search', 'category_id', 'branch_id', 'status', 'condition', 'custodian_id', 'sort_field', 'sort_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenant/Assets/Register/Create', [
            'categories' => AssetCategory::active()->with('parent')->orderBy('name')->get(),
            'branches'   => Branch::active()->orderBy('name')->get(['id', 'name']),
            'vendors'    => Vendor::active()->orderBy('name')->get(['id', 'name', 'code']),
            'users'      => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'category_id'            => 'required|exists:asset_categories,id',
            'item_id'                => 'nullable|exists:items,id',
            'acquisition_date'       => 'required|date',
            'acquisition_cost'       => 'required|numeric|min:0',
            'vendor_id'              => 'nullable|exists:vendors,id',
            'invoice_number'         => 'nullable|string|max:100',
            'po_number'              => 'nullable|string|max:100',
            'grn_number'             => 'nullable|string|max:100',
            'brand'                  => 'nullable|string|max:100',
            'model_number'           => 'nullable|string|max:100',
            'serial_number'          => 'nullable|string|max:100|unique:assets,serial_number',
            'color'                  => 'nullable|string|max:50',
            'specifications'         => 'nullable|string',
            'description'            => 'nullable|string',
            'branch_id'              => 'nullable|exists:branches,id',
            'building'               => 'nullable|string|max:100',
            'floor'                  => 'nullable|string|max:50',
            'room'                   => 'nullable|string|max:100',
            'location_details'       => 'nullable|string|max:255',
            'custodian_id'           => 'nullable|exists:users,id',
            'custodian_assigned_date'=> 'nullable|date',
            'depreciation_method'    => 'required|in:slm,wdv,none',
            'depreciation_rate'      => 'required|numeric|min:0|max:100',
            'useful_life_years'      => 'required|integer|min:1',
            'residual_value_percent' => 'required|numeric|min:0|max:100',
            'depreciation_start_date'=> 'nullable|date',
            'warranty_months'        => 'nullable|integer|min:0',
            'warranty_provider'      => 'nullable|string|max:255',
            'insurance_company'      => 'nullable|string|max:255',
            'insurance_policy_number'=> 'nullable|string|max:100',
            'insured_value'          => 'nullable|numeric|min:0',
            'insurance_expiry_date'  => 'nullable|date',
            'status'                 => 'required|in:active,under_maintenance,disposed,lost,damaged,written_off',
            'condition'              => 'required|in:excellent,good,fair,poor',
            'notes'                  => 'nullable|string',
            'primary_photo'          => 'nullable|image|max:2048',
        ]);

        // Handle primary photo upload
        if ($request->hasFile('primary_photo')) {
            $data['primary_photo'] = $request->file('primary_photo')->store('assets/photos', 'public');
        }

        // Set warranty expiry
        if (!empty($data['acquisition_date']) && !empty($data['warranty_months'])) {
            $data['warranty_expiry_date'] = \Carbon\Carbon::parse($data['acquisition_date'])
                ->addMonths($data['warranty_months'])->toDateString();
        }

        // Set initial NBV
        $data['net_book_value']  = $data['acquisition_cost'];
        $data['created_by']      = auth()->id();
        $data['updated_by']      = auth()->id();

        $asset = Asset::create($data);

        return redirect()->route('tenant.assets.show', $asset->id)
            ->with('success', "Asset {$asset->asset_tag} registered successfully.");
    }

    public function show(Asset $asset): Response
    {
        $asset->load([
            'category', 'branch', 'custodian', 'vendor',
            'transfers.fromBranch', 'transfers.toBranch',
            'transfers.fromCustodian', 'transfers.toCustodian',
            'maintenances.vendor',
            'depreciationSchedules',
            'createdBy',
        ]);

        return Inertia::render('Tenant/Assets/Register/Show', [
            'asset' => $asset,
        ]);
    }

    public function edit(Asset $asset): Response
    {
        $asset->load(['category', 'branch', 'custodian', 'vendor']);

        return Inertia::render('Tenant/Assets/Register/Edit', [
            'asset'      => $asset,
            'categories' => AssetCategory::active()->with('parent')->orderBy('name')->get(),
            'branches'   => Branch::active()->orderBy('name')->get(['id', 'name']),
            'vendors'    => Vendor::active()->orderBy('name')->get(['id', 'name', 'code']),
            'users'      => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'category_id'            => 'required|exists:asset_categories,id',
            'acquisition_date'       => 'required|date',
            'acquisition_cost'       => 'required|numeric|min:0',
            'vendor_id'              => 'nullable|exists:vendors,id',
            'invoice_number'         => 'nullable|string|max:100',
            'brand'                  => 'nullable|string|max:100',
            'model_number'           => 'nullable|string|max:100',
            'serial_number'          => 'nullable|string|max:100|unique:assets,serial_number,' . $asset->id,
            'color'                  => 'nullable|string|max:50',
            'specifications'         => 'nullable|string',
            'description'            => 'nullable|string',
            'branch_id'              => 'nullable|exists:branches,id',
            'building'               => 'nullable|string|max:100',
            'floor'                  => 'nullable|string|max:50',
            'room'                   => 'nullable|string|max:100',
            'location_details'       => 'nullable|string|max:255',
            'custodian_id'           => 'nullable|exists:users,id',
            'custodian_assigned_date'=> 'nullable|date',
            'depreciation_method'    => 'required|in:slm,wdv,none',
            'depreciation_rate'      => 'required|numeric|min:0|max:100',
            'useful_life_years'      => 'required|integer|min:1',
            'residual_value_percent' => 'required|numeric|min:0|max:100',
            'depreciation_start_date'=> 'nullable|date',
            'warranty_months'        => 'nullable|integer|min:0',
            'warranty_provider'      => 'nullable|string|max:255',
            'insurance_company'      => 'nullable|string|max:255',
            'insurance_policy_number'=> 'nullable|string|max:100',
            'insured_value'          => 'nullable|numeric|min:0',
            'insurance_expiry_date'  => 'nullable|date',
            'status'                 => 'required|in:active,under_maintenance,disposed,lost,damaged,written_off',
            'condition'              => 'required|in:excellent,good,fair,poor',
            'notes'                  => 'nullable|string',
            'primary_photo'          => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('primary_photo')) {
            if ($asset->primary_photo) {
                Storage::disk('public')->delete($asset->primary_photo);
            }
            $data['primary_photo'] = $request->file('primary_photo')->store('assets/photos', 'public');
        }

        if (!empty($data['acquisition_date']) && !empty($data['warranty_months'])) {
            $data['warranty_expiry_date'] = \Carbon\Carbon::parse($data['acquisition_date'])
                ->addMonths($data['warranty_months'])->toDateString();
        }

        $data['updated_by'] = auth()->id();
        $asset->update($data);

        return redirect()->route('tenant.assets.show', $asset->id)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        if ($asset->transfers()->whereIn('status', ['pending', 'approved'])->exists()) {
            return back()->with('error', 'Cannot delete asset with pending transfers.');
        }

        if ($asset->primary_photo) {
            Storage::disk('public')->delete($asset->primary_photo);
        }

        $asset->delete();

        return redirect()->route('tenant.assets.index')
            ->with('success', 'Asset deleted successfully.');
    }

    // Additional photo upload
    public function uploadPhoto(Request $request, Asset $asset): RedirectResponse
    {
        $request->validate(['photo' => 'required|image|max:2048']);

        $path   = $request->file('photo')->store('assets/photos', 'public');
        $photos = $asset->photos ?? [];
        $photos[] = $path;
        $asset->update(['photos' => $photos]);

        return back()->with('success', 'Photo uploaded.');
    }

    public function deletePhoto(Request $request, Asset $asset): RedirectResponse
    {
        $request->validate(['photo_path' => 'required|string']);

        $photos = collect($asset->photos ?? [])->filter(fn($p) => $p !== $request->photo_path)->values()->all();
        Storage::disk('public')->delete($request->photo_path);
        $asset->update(['photos' => $photos]);

        return back()->with('success', 'Photo removed.');
    }
}