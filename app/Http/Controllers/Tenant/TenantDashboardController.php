<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Models\GoodsReceiptNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TenantDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId   = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        // ── KPI Stats ─────────────────────────────────────────────────────────
        $pendingApprovals = PurchaseRequisition::pendingMyApproval($userId)->count();

        $myPRs = PurchaseRequisition::where('user_id', $userId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft'    THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status IN ('pending_level_1','pending_level_2','pending_level_3','submitted') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        $myAssetsCount = Asset::where('custodian_id', $userId)
            ->whereIn('status', ['active'])
            ->count();

        $maintenanceDue = AssetMaintenance::whereBetween('scheduled_date', [now(), now()->addDays(7)])
            ->whereIn('status', ['scheduled', 'overdue'])
            ->count();

        $totalAssets = Asset::whereNull('deleted_at')->count();

        $totalAssetValue = Asset::whereNull('deleted_at')->sum('net_book_value');

        // Total PO spend this month
        $monthlySpend = PurchaseOrder::whereMonth('po_date', now()->month)
            ->whereYear('po_date', now()->year)
            ->whereIn('status', ['sent', 'partially_received', 'fully_received', 'closed'])
            ->sum('total_amount');

        // ── Recent PRs (last 8, any user) ─────────────────────────────────────
        $recentPRs = PurchaseRequisition::with(['user:id,name', 'department:id,name', 'branch:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($pr) => [
                'id'           => $pr->id,
                'pr_number'    => $pr->pr_number,
                'requester'    => $pr->user?->name,
                'department'   => $pr->department?->name,
                'branch'       => $pr->branch?->name,
                'total'        => (float) $pr->total_amount,
                'status'       => $pr->status,
                'status_badge' => $pr->status_badge,
                'priority'     => $pr->priority,
                'date'         => $pr->pr_date->format('d M Y'),
                'is_mine'      => $pr->user_id === $userId,
            ]);

        // ── Pending approvals queue ───────────────────────────────────────────
        $pendingQueue = PurchaseRequisition::with(['user:id,name', 'department:id,name'])
            ->pendingMyApproval($userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($pr) => [
                'id'           => $pr->id,
                'pr_number'    => $pr->pr_number,
                'requester'    => $pr->user?->name,
                'department'   => $pr->department?->name,
                'total'        => (float) $pr->total_amount,
                'priority'     => $pr->priority,
                'date'         => $pr->pr_date->format('d M Y'),
                'status_badge' => $pr->status_badge,
            ]);

        // ── Monthly spend trend (last 6 months) ───────────────────────────────
        $monthlyTrend = PurchaseOrder::whereBetween('po_date', [
                now()->subMonths(5)->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->whereIn('status', ['sent', 'partially_received', 'fully_received', 'closed'])
            ->selectRaw("DATE_FORMAT(po_date,'%b') as label, DATE_FORMAT(po_date,'%Y-%m') as sort_key, SUM(total_amount) as value")
            ->groupBy('label', 'sort_key')
            ->orderBy('sort_key')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'value' => (float) $r->value]);

        // ── Asset status distribution ─────────────────────────────────────────
        $assetStatusDist = Asset::whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ── PR status breakdown (for donut) ───────────────────────────────────
        $prStatusDist = PurchaseRequisition::selectRaw("
                SUM(CASE WHEN status = 'draft'    THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status IN ('pending_level_1','pending_level_2','pending_level_3','submitted') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        // ── Upcoming maintenance (next 14 days) ───────────────────────────────
        $upcomingMaintenance = AssetMaintenance::with(['asset:id,asset_tag,name,branch_id', 'asset.branch:id,name'])
            ->whereBetween('scheduled_date', [now(), now()->addDays(14)])
            ->whereIn('status', ['scheduled', 'overdue'])
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get()
            ->map(fn ($m) => [
                'id'             => $m->id,
                'asset_tag'      => $m->asset?->asset_tag,
                'asset_name'     => $m->asset?->name,
                'branch'         => $m->asset?->branch?->name,
                'type'           => $m->maintenance_type,
                'scheduled_date' => $m->scheduled_date?->format('d M Y'),
                'is_overdue'     => $m->status === 'overdue',
                'days_away'      => now()->diffInDays($m->scheduled_date, false),
            ]);

        // ── Recent GRNs ───────────────────────────────────────────────────────
        $recentGRNs = GoodsReceiptNote::with(['vendor:id,name', 'branch:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($g) => [
                'id'         => $g->id,
                'grn_number' => $g->grn_number,
                'vendor'     => $g->vendor?->name,
                'branch'     => $g->branch?->name,
                'date'       => $g->receipt_date?->format('d M Y'),
                'status'     => $g->status,
            ]);

        // ── Top vendors this month ────────────────────────────────────────────
        $topVendors = PurchaseOrder::with('vendor:id,name')
            ->whereMonth('po_date', now()->month)
            ->whereYear('po_date', now()->year)
            ->whereIn('status', ['sent', 'partially_received', 'fully_received', 'closed'])
            ->selectRaw('vendor_id, SUM(total_amount) as total')
            ->groupBy('vendor_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($po) => [
                'name'  => $po->vendor?->name ?? 'Unknown',
                'total' => (float) $po->total,
            ]);

        return Inertia::render('Tenant/Dashboard', [
            'stats' => [
                'pending_approvals' => $pendingApprovals,
                'my_prs'            => $myPRs,
                'my_assets'         => $myAssetsCount,
                'maintenance_due'   => $maintenanceDue,
                'total_assets'      => $totalAssets,
                'total_asset_value' => (float) $totalAssetValue,
                'monthly_spend'     => (float) $monthlySpend,
            ],
            'recent_prs'          => $recentPRs,
            'pending_queue'       => $pendingQueue,
            'monthly_trend'       => $monthlyTrend,
            'asset_status_dist'   => $assetStatusDist,
            'pr_status_dist'      => $prStatusDist,
            'upcoming_maintenance'=> $upcomingMaintenance,
            'recent_grns'         => $recentGRNs,
            'top_vendors'         => $topVendors,
        ]);
    }
}