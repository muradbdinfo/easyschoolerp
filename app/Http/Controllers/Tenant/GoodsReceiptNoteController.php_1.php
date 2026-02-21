<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceiptNote;
use App\Models\GrnItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptNoteController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): Response
    {
        $query = GoodsReceiptNote::with([
            'purchaseOrder:id,po_number',
            'vendor:id,name,code',
            'branch:id,name',
            'receiver:id,name',
        ]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('grn_number', 'like', "%{$s}%")
                  ->orWhere('supplier_invoice_no', 'like', "%{$s}%")
                  ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'like', "%{$s}%"))
            );
        }
        if ($request->filled('status'))    { $query->where('overall_status', $request->status); }
        if ($request->filled('branch_id')) { $query->where('branch_id', $request->branch_id); }
        if ($request->filled('date_from')) { $query->where('receipt_date', '>=', $request->date_from); }
        if ($request->filled('date_to'))   { $query->where('receipt_date', '<=', $request->date_to); }

        $grns = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tenant/Procurement/GRN/GRN_Index', [
            'grns'    => $grns,
            'filters' => $request->only(['search', 'status', 'branch_id', 'date_from', 'date_to']),
            'branches'=> \App\Models\Branch::select('id', 'name')->orderBy('name')->get(),
            'stats'   => [
                'total'   => GoodsReceiptNote::count(),
                'passed'  => GoodsReceiptNote::where('overall_status', 'passed')->count(),
                'partial' => GoodsReceiptNote::where('overall_status', 'partial')->count(),
                'failed'  => GoodsReceiptNote::where('overall_status', 'failed')->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        // Load sent POs with unreceived items
        $poQuery = PurchaseOrder::with(['vendor:id,name', 'branch:id,name', 'items.item:id,name,unit,is_asset,asset_threshold_amount'])
            ->whereIn('status', ['sent', 'acknowledged', 'partial'])
            ->select('id', 'po_number', 'vendor_id', 'branch_id', 'subtotal', 'vat_percentage', 'vat_amount', 'freight_charges', 'discount_amount', 'total_amount');

        if ($request->filled('po_id')) {
            $poQuery->where('id', $request->po_id);
        }

        $availablePOs = $poQuery->latest()->get()->map(function ($po) {
            $po->items->each(fn($item) => $item->pending_quantity = max(0,
                (float)$item->quantity - (float)$item->received_quantity
            ));
            return $po;
        });

        $selectedPO = $request->filled('po_id')
            ? $availablePOs->firstWhere('id', (int)$request->po_id)
            : null;

        return Inertia::render('Tenant/Procurement/GRN/GRN_Create', [
            'availablePOs' => $availablePOs,
            'selectedPO'   => $selectedPO,
            'users'        => User::select('id', 'name')->orderBy('name')->get(),
            'branches'     => \App\Models\Branch::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_order_id'   => 'required|exists:purchase_orders,id',
            'receipt_date'        => 'required|date|before_or_equal:today',
            'received_by'         => 'required|exists:users,id',
            'supplier_invoice_no' => 'nullable|string|max:100',
            'supplier_delivery_note' => 'nullable|string|max:100',
            'vehicle_number'      => 'nullable|string|max:50',
            'overall_status'      => 'required|in:passed,failed,partial',
            'quality_checked_by'  => 'nullable|exists:users,id',
            'quality_remarks'     => 'nullable|string|max:1000',
            'notes'               => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.item_id'     => 'required|exists:items,id',
            'items.*.item_name'   => 'required|string',
            'items.*.unit'        => 'required|string',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.quantity_ordered'  => 'required|numeric|min:0',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_accepted' => 'required|numeric|min:0',
            'items.*.quantity_rejected' => 'required|numeric|min:0',
            'items.*.rejection_reason'  => 'nullable|string|max:500',
        ]);

        try {
            $grn = DB::transaction(function () use ($data, $request) {

                // Handle photo uploads
                $photos = [];
                if ($request->hasFile('photos')) {
                    foreach ($request->file('photos') as $photo) {
                        $path = $photo->store('grn-photos/' . now()->format('Y/m'), 'public');
                        $photos[] = $path;
                    }
                }

                $grn = GoodsReceiptNote::create([
                    'purchase_order_id'      => $data['purchase_order_id'],
                    'vendor_id'              => PurchaseOrder::find($data['purchase_order_id'])->vendor_id,
                    'branch_id'              => PurchaseOrder::find($data['purchase_order_id'])->branch_id,
                    'receipt_date'           => $data['receipt_date'],
                    'received_by'            => $data['received_by'],
                    'supplier_invoice_no'    => $data['supplier_invoice_no'] ?? null,
                    'supplier_delivery_note' => $data['supplier_delivery_note'] ?? null,
                    'vehicle_number'         => $data['vehicle_number'] ?? null,
                    'overall_status'         => $data['overall_status'],
                    'quality_checked_by'     => $data['quality_checked_by'] ?? null,
                    'quality_remarks'        => $data['quality_remarks'] ?? null,
                    'notes'                  => $data['notes'] ?? null,
                    'photos'                 => $photos ?: null,
                    'created_by'             => auth()->id(),
                ]);

                // Create GRN items & update PO item received quantities
                foreach ($data['items'] as $itemData) {
                    GrnItem::create([
                        'grn_id'                 => $grn->id,
                        'purchase_order_item_id' => $itemData['purchase_order_item_id'],
                        'item_id'                => $itemData['item_id'],
                        'item_name'              => $itemData['item_name'],
                        'unit'                   => $itemData['unit'],
                        'unit_price'             => $itemData['unit_price'],
                        'quantity_ordered'       => $itemData['quantity_ordered'],
                        'quantity_received'      => $itemData['quantity_received'],
                        'quantity_accepted'      => $itemData['quantity_accepted'],
                        'quantity_rejected'      => $itemData['quantity_rejected'],
                        'rejection_reason'       => $itemData['rejection_reason'] ?? null,
                    ]);

                    // Update PO item received quantity
                    PurchaseOrderItem::where('id', $itemData['purchase_order_item_id'])
                        ->increment('received_quantity', $itemData['quantity_accepted']);
                }

                // Update PO status based on receipt
                $this->updatePOStatus($data['purchase_order_id']);

                return $grn;
            });

            // Notify the PO creator / requester
            $this->notifyGRNCreated($grn);

            return redirect()
                ->route('tenant.grn.show', $grn)
                ->with('success', "GRN {$grn->grn_number} created successfully.");

        } catch (\Exception $e) {
            Log::error('GRN creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create GRN. Please try again.');
        }
    }

    public function show(GoodsReceiptNote $grn): Response
    {
        $grn->load([
            'purchaseOrder:id,po_number,subtotal,vat_percentage,vat_amount,freight_charges,discount_amount,total_amount',
            'vendor:id,name,code,phone,email',
            'branch:id,name',
            'receiver:id,name',
            'qualityChecker:id,name',
            'creator:id,name',
            'items.item:id,name,code,is_asset,asset_threshold_amount',
        ]);

        return Inertia::render('Tenant/Procurement/GRN/GRN_Show', [
            'grn' => $grn,
        ]);
    }

    // â”€â”€ Private Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function updatePOStatus(int $poId): void
    {
        $po = PurchaseOrder::with('items')->find($poId);

        $allReceived = $po->items->every(fn($item) =>
            (float)$item->received_quantity >= (float)$item->quantity
        );

        $anyReceived = $po->items->some(fn($item) =>
            (float)$item->received_quantity > 0
        );

        if ($allReceived) {
            $po->update(['status' => 'received']);
        } elseif ($anyReceived) {
            $po->update(['status' => 'partial']);
        }
    }

    private function notifyGRNCreated(GoodsReceiptNote $grn): void
    {
        try {
            $po      = $grn->purchaseOrder()->with('creator')->first();
            $creator = $po?->creator;

            if ($creator) {
                $this->notifications->create(
                    $creator,
                    'grn_created',
                    'Goods Received',
                    "GRN {$grn->grn_number} created for PO {$po->po_number}. Status: {$grn->overall_status}.",
                    route('tenant.grn.show', $grn->id)
                );
            }
        } catch (\Exception $e) {
            Log::warning('GRN notification failed: ' . $e->getMessage());
        }
    }
}