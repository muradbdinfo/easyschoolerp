<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetBranch;
use App\Models\AssetCategory;
use App\Models\User;
use App\Models\VerificationCycle;
use App\Models\VerificationItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetVerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $cycles = VerificationCycle::withCount([
                'items',
                'items as verified_count'    => fn($q) => $q->whereNotNull('verified_at'),
                'items as discrepancy_count' => fn($q) => $q->whereNotNull('verified_at')->where('severity', '!=', null),
            ])
            ->when($request->year, fn($q) => $q->where('cycle_year', $request->year))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->through(fn($c) => [
                'id'                 => $c->id,
                'name'               => $c->name,
                'cycle_year'         => $c->cycle_year,
                'start_date'         => $c->start_date?->format('d M Y'),
                'end_date'           => $c->end_date?->format('d M Y'),
                'total_assets'       => $c->total_assets,
                'verified_count'     => $c->verified_count,
                'discrepancy_count'  => $c->discrepancy_count,
                'progress_percent'   => $c->progress_percent,
                'status'             => $c->status,
                'status_badge'       => $c->status_badge,
            ]);

        return Inertia::render('Tenant/Assets/Verification/Index', [
            'cycles'  => $cycles,
            'filters' => $request->only(['year', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenant/Assets/Verification/Create', [
            'assetCount' => Asset::active()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'cycle_year'   => 'required|integer|min:2020|max:2100',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'scope'        => 'required|in:all,branch,category',
            'scope_ids'    => 'nullable|array',
            'team_members' => 'nullable|array',
        ]);

        // Determine assets in scope
        $assetQuery = Asset::active();
        if ($data['scope'] === 'branch' && !empty($data['scope_ids'])) {
            $assetQuery->whereIn('branch_id', $data['scope_ids']);
        } elseif ($data['scope'] === 'category' && !empty($data['scope_ids'])) {
            $assetQuery->whereIn('category_id', $data['scope_ids']);
        }
        $assets = $assetQuery->get();

        $cycle = VerificationCycle::create([
            ...$data,
            'total_assets' => $assets->count(),
            'status'       => 'planning',
            'created_by'   => auth()->id(),
        ]);

        // Create blank verification items
        $items = $assets->map(fn($a) => [
            'cycle_id'   => $cycle->id,
            'asset_id'   => $a->id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        foreach (array_chunk($items, 500) as $chunk) {
            VerificationItem::insert($chunk);
        }

        return redirect()->route('tenant.assets.verification.show', $cycle)
            ->with('success', "Cycle '{$cycle->name}' created with {$assets->count()} assets.");
    }

    public function show(VerificationCycle $cycle, Request $request): Response
    {
        $items = VerificationItem::with(['asset.category', 'asset.branch'])
            ->where('cycle_id', $cycle->id)
            ->when($request->filter === 'pending', fn($q) => $q->whereNull('verified_at'))
            ->when($request->filter === 'verified', fn($q) => $q->whereNotNull('verified_at'))
            ->when($request->filter === 'discrepancy', fn($q) => $q->whereNotNull('severity'))
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('asset', fn($a) => $a->where('asset_tag', 'like', "%$s%")->orWhere('name', 'like', "%$s%"))
            )
            ->paginate(20)
            ->through(fn($i) => [
                'id'              => $i->id,
                'asset_id'        => $i->asset_id,
                'asset_tag'       => $i->asset->asset_tag,
                'asset_name'      => $i->asset->name,
                'category'        => $i->asset->category?->name,
                'branch'          => $i->asset->branch?->name,
                'location'        => trim("{$i->asset->building} {$i->asset->room}"),
                'verified_at'     => $i->verified_at?->format('d M Y H:i'),
                'has_discrepancy' => $i->has_discrepancy,
                'severity'        => $i->severity,
                'severity_badge'  => $i->severity_badge,
                'condition'       => $i->condition,
                'is_present'      => $i->is_present,
            ]);

        return Inertia::render('Tenant/Assets/Verification/Show', [
            'cycle'   => array_merge($cycle->toArray(), [
                'status_badge'   => $cycle->status_badge,
                'progress_percent' => $cycle->progress_percent,
            ]),
            'items'   => $items,
            'filters' => $request->only(['filter', 'search']),
            'summary' => [
                'total'       => $cycle->total_assets,
                'verified'    => $cycle->verified_count,
                'discrepancy' => $cycle->discrepancy_count,
                'pending'     => $cycle->total_assets - $cycle->verified_count,
            ],
        ]);
    }

    public function verify(Request $request, VerificationCycle $cycle, VerificationItem $item): RedirectResponse
    {
        $data = $request->validate([
            'is_present'        => 'required|boolean',
            'location_correct'  => 'required|boolean',
            'custodian_correct' => 'required|boolean',
            'condition'         => 'required|in:excellent,good,fair,poor',
            'actual_location'   => 'nullable|string|max:255',
            'actual_custodian'  => 'nullable|string|max:255',
            'discrepancy_details' => 'nullable|string',
            'severity'          => 'nullable|in:low,medium,high',
        ]);

        $hasDiscrepancy = !$data['is_present'] || !$data['location_correct']
            || !$data['custodian_correct'] || in_array($data['condition'], ['fair', 'poor']);

        $item->update([
            ...$data,
            'resolution_status' => $hasDiscrepancy ? 'reported' : null,
            'verified_by'       => auth()->id(),
            'verified_at'       => now(),
        ]);

        // Update cycle counts
        $cycle->increment('verified_count');
        if ($hasDiscrepancy) {
            $cycle->increment('discrepancy_count');
        }

        // Mark in_progress when first verification done
        if ($cycle->status === 'planning') {
            $cycle->update(['status' => 'in_progress']);
        }

        // Auto-complete if all verified
        if ($cycle->verified_count >= $cycle->total_assets) {
            $cycle->update(['status' => 'completed', 'completed_at' => now()]);
        }

        return back()->with('success', 'Asset verified.');
    }

    public function complete(VerificationCycle $cycle): RedirectResponse
    {
        $cycle->update(['status' => 'completed', 'completed_at' => now()]);

        return redirect()->route('tenant.assets.verification.show', $cycle)
            ->with('success', 'Verification cycle marked as completed.');
    }

    public function resolveDiscrepancy(Request $request, VerificationItem $item): RedirectResponse
    {
        $data = $request->validate([
            'resolution_status' => 'required|in:investigating,resolved',
            'resolution_notes'  => 'required|string',
        ]);

        $item->update($data);

        return back()->with('success', 'Discrepancy updated.');
    }
}