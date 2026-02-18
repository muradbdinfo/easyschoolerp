<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetMaintenanceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AssetMaintenance::with([
            'asset:id,asset_tag,name,branch_id',
            'asset.branch:id,name',
            'vendor:id,name',
            'assignedTo:id,name',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('maintenance_number', 'like', "%{$search}%")
                  ->orWhereHas('asset', fn($a) => $a->where('asset_tag', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('type'))    $query->where('type', $request->type);

        // Summary stats
        $stats = [
            'scheduled'   => AssetMaintenance::where('status', 'scheduled')->count(),
            'in_progress' => AssetMaintenance::where('status', 'in_progress')->count(),
            'overdue'     => AssetMaintenance::overdue()->count(),
            'due_soon'    => AssetMaintenance::dueSoon(7)->count(),
        ];

        $maintenances = $query->orderBy('scheduled_date', 'asc')->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Assets/Maintenance/Index', [
            'maintenances' => $maintenances,
            'stats'        => $stats,
            'filters'      => $request->only(['search', 'status', 'type']),
        ]);
    }

    public function create(Request $request): Response
    {
        $asset = null;
        if ($request->filled('asset_id')) {
            $asset = Asset::with('branch')->find($request->asset_id);
        }

        return Inertia::render('Tenant/Assets/Maintenance/Create', [
            'preSelectedAsset' => $asset,
            'assets'   => Asset::active()->orderBy('asset_tag')->get(['id', 'asset_tag', 'name']),
            'vendors'  => Vendor::active()->orderBy('name')->get(['id', 'name']),
            'users'    => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id'       => 'required|exists:assets,id',
            'type'           => 'required|in:routine,repair,servicing,calibration,upgrade',
            'frequency'      => 'required|in:one_time,monthly,quarterly,yearly',
            'scheduled_date' => 'required|date',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'estimated_cost' => 'nullable|numeric|min:0',
            'description'    => 'nullable|string|max:1000',
            'assigned_to'    => 'nullable|exists:users,id',
            'notes'          => 'nullable|string',
        ]);

        $data['status']     = 'scheduled';
        $data['created_by'] = auth()->id();

        AssetMaintenance::create($data);

        return redirect()->route('tenant.assets.maintenance.index')
            ->with('success', 'Maintenance scheduled successfully.');
    }

    public function show(AssetMaintenance $assetMaintenance): Response
    {
        $assetMaintenance->load([
            'asset.category', 'asset.branch',
            'vendor', 'assignedTo', 'createdBy',
        ]);

        return Inertia::render('Tenant/Assets/Maintenance/Show', [
            'maintenance' => $assetMaintenance,
        ]);
    }

    public function edit(AssetMaintenance $assetMaintenance): Response
    {
        $assetMaintenance->load(['asset', 'vendor', 'assignedTo']);

        return Inertia::render('Tenant/Assets/Maintenance/Edit', [
            'maintenance' => $assetMaintenance,
            'vendors' => Vendor::active()->orderBy('name')->get(['id', 'name']),
            'users'   => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $data = $request->validate([
            'type'            => 'required|in:routine,repair,servicing,calibration,upgrade',
            'frequency'       => 'required|in:one_time,monthly,quarterly,yearly',
            'scheduled_date'  => 'required|date',
            'completed_date'  => 'nullable|date',
            'vendor_id'       => 'nullable|exists:vendors,id',
            'estimated_cost'  => 'nullable|numeric|min:0',
            'actual_cost'     => 'nullable|numeric|min:0',
            'invoice_number'  => 'nullable|string|max:100',
            'description'     => 'nullable|string|max:1000',
            'work_performed'  => 'nullable|string',
            'parts_replaced'  => 'nullable|string',
            'status'          => 'required|in:scheduled,in_progress,completed,cancelled',
            'condition_after' => 'nullable|in:excellent,good,fair,poor',
            'next_due_date'   => 'nullable|date',
            'assigned_to'     => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
        ]);

        $assetMaintenance->update($data);

        // Update asset condition if maintenance completed
        if ($data['status'] === 'completed' && !empty($data['condition_after'])) {
            $assetMaintenance->asset->update([
                'condition' => $data['condition_after'],
                'status'    => 'active',
            ]);
        }

        return redirect()->route('tenant.assets.maintenance.show', $assetMaintenance)
            ->with('success', 'Maintenance updated successfully.');
    }

    public function destroy(AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $assetMaintenance->delete();
        return redirect()->route('tenant.assets.maintenance.index')
            ->with('success', 'Maintenance record deleted.');
    }

    // Quick complete from list
    public function complete(Request $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $data = $request->validate([
            'completed_date'  => 'required|date',
            'work_performed'  => 'required|string|max:1000',
            'actual_cost'     => 'nullable|numeric|min:0',
            'condition_after' => 'nullable|in:excellent,good,fair,poor',
        ]);

        $assetMaintenance->update(array_merge($data, ['status' => 'completed']));

        if (!empty($data['condition_after'])) {
            $assetMaintenance->asset->update([
                'condition' => $data['condition_after'],
                'status'    => 'active',
            ]);
        }

        return back()->with('success', 'Maintenance marked as completed.');
    }
}