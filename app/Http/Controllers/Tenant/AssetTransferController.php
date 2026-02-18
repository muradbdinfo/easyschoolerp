<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AssetTransfer::with([
            'asset:id,asset_tag,name',
            'fromBranch:id,name', 'toBranch:id,name',
            'fromCustodian:id,name', 'toCustodian:id,name',
            'createdBy:id,name',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhereHas('asset', fn($a) => $a->where('asset_tag', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_branch_id', $request->branch_id)
                  ->orWhere('to_branch_id', $request->branch_id);
            });
        }

        $transfers = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Assets/Transfers/Index', [
            'transfers' => $transfers,
            'branches'  => Branch::active()->orderBy('name')->get(['id', 'name']),
            'filters'   => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $asset = null;
        if ($request->filled('asset_id')) {
            $asset = Asset::with(['branch', 'custodian'])->find($request->asset_id);
        }

        return Inertia::render('Tenant/Assets/Transfers/Create', [
            'preSelectedAsset' => $asset,
            'assets'    => Asset::active()->with('branch', 'custodian')
                ->orderBy('asset_tag')->get(['id', 'asset_tag', 'name', 'branch_id', 'custodian_id']),
            'branches'  => Branch::active()->orderBy('name')->get(['id', 'name']),
            'users'     => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id'           => 'required|exists:assets,id',
            'to_branch_id'       => 'required|exists:branches,id',
            'to_location'        => 'nullable|string|max:255',
            'to_custodian_id'    => 'nullable|exists:users,id',
            'transfer_date'      => 'required|date',
            'reason'             => 'required|string|max:1000',
            'condition_before'   => 'nullable|in:excellent,good,fair,poor',
            'notes'              => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($data['asset_id']);

        // Capture current values
        $data['from_branch_id']    = $asset->branch_id;
        $data['from_location']     = $asset->location_details;
        $data['from_custodian_id'] = $asset->custodian_id;
        $data['created_by']        = auth()->id();

        // Determine if approval needed (cross-branch)
        $needsApproval = $data['from_branch_id'] && $data['to_branch_id']
            && $data['from_branch_id'] !== $data['to_branch_id'];

        $data['status'] = $needsApproval ? 'pending' : 'completed';

        $transfer = AssetTransfer::create($data);

        // If no approval needed, update asset immediately
        if (!$needsApproval) {
            $asset->update([
                'branch_id'              => $data['to_branch_id'],
                'location_details'       => $data['to_location'],
                'custodian_id'           => $data['to_custodian_id'],
                'custodian_assigned_date'=> $data['transfer_date'],
            ]);
        }

        $msg = $needsApproval
            ? 'Transfer request submitted and pending approval.'
            : 'Asset transferred successfully.';

        return redirect()->route('tenant.assets.transfers.index')
            ->with('success', $msg);
    }

    public function show(AssetTransfer $assetTransfer): Response
    {
        $assetTransfer->load([
            'asset.category', 'asset.branch',
            'fromBranch', 'toBranch',
            'fromCustodian', 'toCustodian',
            'approvedBy', 'createdBy',
        ]);

        return Inertia::render('Tenant/Assets/Transfers/Show', [
            'transfer' => $assetTransfer,
        ]);
    }

    public function approve(AssetTransfer $assetTransfer): RedirectResponse
    {
        if ($assetTransfer->status !== 'pending') {
            return back()->with('error', 'Transfer is not pending approval.');
        }

        $assetTransfer->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Update asset location
        $asset = $assetTransfer->asset;
        $asset->update([
            'branch_id'              => $assetTransfer->to_branch_id,
            'location_details'       => $assetTransfer->to_location,
            'custodian_id'           => $assetTransfer->to_custodian_id,
            'custodian_assigned_date'=> $assetTransfer->transfer_date,
        ]);

        $assetTransfer->update(['status' => 'completed']);

        return back()->with('success', 'Transfer approved and asset location updated.');
    }

    public function reject(Request $request, AssetTransfer $assetTransfer): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        if ($assetTransfer->status !== 'pending') {
            return back()->with('error', 'Transfer is not pending approval.');
        }

        $assetTransfer->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Transfer rejected.');
    }
}