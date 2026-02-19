<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceiptNote;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Vendor;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    // ─── Procurement Reports ────────────────────────────────────────────────

    public function procurement(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo   = $request->get('date_to',   now()->toDateString());
        $branchId = $request->get('branch_id');

        // ── PR Register ──
        $prQuery = PurchaseRequisition::with(['user:id,name', 'department:id,name', 'branch:id,name'])
            ->whereBetween('pr_date', [$dateFrom, $dateTo]);
        if ($branchId) $prQuery->where('branch_id', $branchId);
        $prRegister = $prQuery->orderByDesc('pr_date')->get()->map(fn($pr) => [
            'id'         => $pr->id,
            'pr_number'  => $pr->pr_number,
            'pr_date'    => $pr->pr_date->format('d M Y'),
            'requester'  => $pr->user?->name,
            'department' => $pr->department?->name,
            'branch'     => $pr->branch?->name,
            'items_count'=> $pr->items_count ?? 0,
            'total'      => (float)$pr->total_amount,
            'status'     => $pr->status,
            'status_badge'=> $pr->status_badge,
        ]);

        // ── PO Register ──
        $poQuery = PurchaseOrder::with(['vendor:id,name', 'branch:id,name'])
            ->whereBetween('po_date', [$dateFrom, $dateTo]);
        if ($branchId) $poQuery->where('branch_id', $branchId);
        $poRegister = $poQuery->orderByDesc('po_date')->get()->map(fn($po) => [
            'id'         => $po->id,
            'po_number'  => $po->po_number,
            'po_date'    => $po->po_date->format('d M Y'),
            'vendor'     => $po->vendor?->name,
            'branch'     => $po->branch?->name,
            'total'      => (float)$po->total_amount,
            'status'     => $po->status,
            'status_badge'=> $po->status_badge,
        ]);

        // ── GRN Register ──
        $grnQuery = GoodsReceiptNote::with(['vendor:id,name', 'branch:id,name', 'purchaseOrder:id,po_number'])
            ->whereBetween('receipt_date', [$dateFrom, $dateTo]);
        if ($branchId) $grnQuery->where('branch_id', $branchId);
        $grnRegister = $grnQuery->orderByDesc('receipt_date')->get()->map(fn($grn) => [
            'id'          => $grn->id,
            'grn_number'  => $grn->grn_number,
            'receipt_date'=> $grn->receipt_date->format('d M Y'),
            'po_number'   => $grn->purchaseOrder?->po_number,
            'vendor'      => $grn->vendor?->name,
            'branch'      => $grn->branch?->name,
            'status'      => $grn->overall_status,
        ]);

        // ── Vendor-wise spending chart ──
        $vendorSpending = PurchaseOrder::join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->whereBetween('po_date', [$dateFrom, $dateTo])
            ->when($branchId, fn($q) => $q->where('purchase_orders.branch_id', $branchId))
            ->groupBy('vendors.id', 'vendors.name')
            ->selectRaw('vendors.name as vendor, SUM(total_amount) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Department-wise spending ──
        $deptSpending = PurchaseRequisition::join('departments', 'purchase_requisitions.department_id', '=', 'departments.id')
            ->whereBetween('pr_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'po_created', 'partially_received', 'fully_received', 'closed'])
            ->when($branchId, fn($q) => $q->where('purchase_requisitions.branch_id', $branchId))
            ->groupBy('departments.id', 'departments.name')
            ->selectRaw('departments.name as department, SUM(total_amount) as total')
            ->orderByDesc('total')
            ->get();

        // ── Monthly spending trend (last 12 months) ──
        $monthlyTrend = PurchaseOrder::whereBetween('po_date', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw("DATE_FORMAT(po_date, '%Y-%m') as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Summary stats ──
        $summary = [
            'total_prs'     => PurchaseRequisition::whereBetween('pr_date', [$dateFrom, $dateTo])->count(),
            'total_pos'     => PurchaseOrder::whereBetween('po_date', [$dateFrom, $dateTo])->count(),
            'total_grns'    => GoodsReceiptNote::whereBetween('receipt_date', [$dateFrom, $dateTo])->count(),
            'total_spend'   => PurchaseOrder::whereBetween('po_date', [$dateFrom, $dateTo])->sum('total_amount'),
            'pending_prs'   => PurchaseRequisition::whereIn('status', ['submitted','pending_level_1','pending_level_2','pending_level_3'])->count(),
        ];

        $branches    = Branch::orderBy('name')->get(['id','name']);
        $departments = Department::orderBy('name')->get(['id','name']);

        return Inertia::render('Tenant/Reports/Procurement', compact(
            'prRegister', 'poRegister', 'grnRegister',
            'vendorSpending', 'deptSpending', 'monthlyTrend',
            'summary', 'branches', 'departments',
            'dateFrom', 'dateTo', 'branchId'
        ));
    }

    // ─── Asset Reports ───────────────────────────────────────────────────────

    public function assets(Request $request)
    {
        $branchId   = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        // ── Asset Valuation ──
        $valuation = Asset::with(['category:id,name', 'branch:id,name'])
            ->when($branchId,   fn($q) => $q->where('branch_id', $branchId))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->whereIn('status', ['active','under_maintenance'])
            ->get()
            ->map(fn($a) => [
                'id'              => $a->id,
                'asset_tag'       => $a->asset_tag,
                'name'            => $a->name,
                'category'        => $a->category?->name,
                'branch'          => $a->branch?->name,
                'acquisition_cost'=> (float)$a->acquisition_cost,
                'accumulated_dep' => (float)$a->accumulated_depreciation,
                'net_book_value'  => (float)$a->net_book_value,
                'status'          => $a->status,
            ]);

        // ── Category distribution (pie chart) ──
        $categoryDistribution = Asset::join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->when($branchId, fn($q) => $q->where('assets.branch_id', $branchId))
            ->whereNull('assets.deleted_at')
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->selectRaw('asset_categories.name as category, COUNT(*) as count, SUM(net_book_value) as nbv')
            ->orderByDesc('count')
            ->get();

        // ── Warranty expiring in next 90 days ──
        $warrantyExpiring = Asset::with(['branch:id,name', 'custodian:id,name'])
            ->whereNotNull('warranty_expiry_date')
            ->whereBetween('warranty_expiry_date', [now(), now()->addDays(90)])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warranty_expiry_date')
            ->get()
            ->map(fn($a) => [
                'id'              => $a->id,
                'asset_tag'       => $a->asset_tag,
                'name'            => $a->name,
                'branch'          => $a->branch?->name,
                'custodian'       => $a->custodian?->name,
                'expiry'          => $a->warranty_expiry_date->format('d M Y'),
                'days_remaining'  => now()->diffInDays($a->warranty_expiry_date),
            ]);

        // ── Maintenance due in next 30 days ──
        $maintenanceDue = AssetMaintenance::with(['asset:id,asset_tag,name', 'asset.branch:id,name'])
            ->whereBetween('scheduled_date', [now(), now()->addDays(30)])
            ->where('status', 'scheduled')
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id,
                'asset_tag'      => $m->asset?->asset_tag,
                'asset_name'     => $m->asset?->name,
                'branch'         => $m->asset?->branch?->name,
                'type'           => $m->maintenance_type,
                'scheduled_date' => $m->scheduled_date->format('d M Y'),
                'days_remaining' => now()->diffInDays($m->scheduled_date, false),
                'status'         => $m->status,
            ]);

        // ── Depreciation summary ──
        $depreciationSummary = Asset::join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->when($branchId, fn($q) => $q->where('assets.branch_id', $branchId))
            ->whereNull('assets.deleted_at')
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->selectRaw('
                asset_categories.name as category,
                COUNT(*) as asset_count,
                SUM(assets.acquisition_cost) as total_cost,
                SUM(assets.accumulated_depreciation) as total_depreciation,
                SUM(assets.net_book_value) as total_nbv
            ')
            ->get();

        // ── Summary stats ──
        $summary = [
            'total_assets'       => Asset::when($branchId, fn($q) => $q->where('branch_id', $branchId))->whereIn('status',['active','under_maintenance'])->count(),
            'total_cost'         => Asset::when($branchId, fn($q) => $q->where('branch_id', $branchId))->sum('acquisition_cost'),
            'total_nbv'          => Asset::when($branchId, fn($q) => $q->where('branch_id', $branchId))->sum('net_book_value'),
            'total_depreciation' => Asset::when($branchId, fn($q) => $q->where('branch_id', $branchId))->sum('accumulated_depreciation'),
            'warranty_expiring_soon' => Asset::whereNotNull('warranty_expiry_date')->whereBetween('warranty_expiry_date',[now(),now()->addDays(30)])->count(),
            'maintenance_due'    => AssetMaintenance::whereBetween('scheduled_date',[now(),now()->addDays(7)])->whereIn('status',['scheduled','overdue'])->count(),
        ];

        $branches   = Branch::orderBy('name')->get(['id','name']);
        $categories = \App\Models\AssetCategory::orderBy('name')->get(['id','name']);

        return Inertia::render('Tenant/Reports/Assets', compact(
            'valuation', 'categoryDistribution', 'warrantyExpiring',
            'maintenanceDue', 'depreciationSummary', 'summary',
            'branches', 'categories', 'branchId', 'categoryId'
        ));
    }

    // ─── Dashboard Stats (for enhanced dashboard) ────────────────────────────

    public function dashboardStats()
    {
        $stats = [
            'pending_approvals'  => PurchaseRequisition::whereIn('status', ['submitted','pending_level_1','pending_level_2','pending_level_3'])->count(),
            'my_requisitions'    => PurchaseRequisition::where('user_id', auth()->id())->count(),
            'my_assets'          => Asset::where('custodian_id', auth()->id())->whereIn('status',['active'])->count(),
            'maintenance_due'    => AssetMaintenance::whereBetween('scheduled_date', [now(), now()->addDays(7)])->whereIn('status',['scheduled','overdue'])->count(),
        ];

        $recentPRs = PurchaseRequisition::with(['user:id,name','department:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($pr) => [
                'id'          => $pr->id,
                'pr_number'   => $pr->pr_number,
                'requester'   => $pr->user?->name,
                'department'  => $pr->department?->name,
                'total'       => (float)$pr->total_amount,
                'status'      => $pr->status,
                'status_badge'=> $pr->status_badge,
                'date'        => $pr->pr_date->format('d M Y'),
            ]);

        $monthlySpend = PurchaseOrder::whereBetween('po_date', [now()->subMonths(5)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(po_date,'%b %Y') as month, DATE_FORMAT(po_date,'%Y-%m') as sort_key, SUM(total_amount) as total")
            ->groupBy('month','sort_key')
            ->orderBy('sort_key')
            ->get();

        $assetsByStatus = Asset::selectRaw("status, COUNT(*) as count")
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->pluck('count','status');

        return response()->json(compact('stats','recentPRs','monthlySpend','assetsByStatus'));
    }
}