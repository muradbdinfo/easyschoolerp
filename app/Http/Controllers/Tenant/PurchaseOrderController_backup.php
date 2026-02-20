<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PurchaseOrder::with([
            'vendor:id,name,code,email',
            'branch:id,name',
            'department:id,name',
            'creator:id,name',
            'items',
        ]);

        if ($request->filled('search'))     { $query->search($request->search); }
        if ($request->filled('status'))     { $query->where('status', $request->status); }
        if ($request->filled('vendor_id'))  { $query->where('vendor_id', $request->vendor_id); }
        if ($request->filled('branch_id'))  { $query->where('branch_id', $request->branch_id); }
        if ($request->filled('date_from'))  { $query->where('po_date', '>=', $request->date_from); }
        if ($request->filled('date_to'))    { $query->where('po_date', '<=', $request->date_to); }

        $pos = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tenant/Procurement/PurchaseOrders/PO_Index', [
            'purchaseOrders' => $pos,
            'filters'        => $request->only(['search', 'status', 'vendor_id', 'branch_id', 'date_from', 'date_to']),
            'vendors'        => Vendor::active()->select('id', 'name', 'code')->orderBy('name')->get(),
            'branches'       => Branch::select('id', 'name')->orderBy('name')->get(),
            'stats'          => [
                'total'    => PurchaseOrder::count(),
                'draft'    => PurchaseOrder::where('status', 'draft')->count(),
                'sent'     => PurchaseOrder::where('status', 'sent')->count(),
                'pending'  => PurchaseOrder::whereNotIn('status', ['received', 'closed', 'cancelled'])->count(),
                'received' => PurchaseOrder::where('status', 'received')->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        // Pre-fill from approved PR if provided
        $pr = null;
        if ($request->filled('from_pr')) {
            $pr = PurchaseRequisition::with(['items.item', 'department', 'branch'])
                ->where('status', 'approved')
                ->findOrFail($request->from_pr);
        }

        return Inertia::render('Tenant/Procurement/PurchaseOrders/PO_Create', [
            'vendors'     => Vendor::active()->select('id', 'name', 'code', 'payment_terms_days', 'email')->orderBy('name')->get(),
            'branches'    => Branch::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'items'       => Item::active()->select('id', 'name', 'code', 'unit', 'current_price')->orderBy('name')->get(),
            'fromPR'      => $pr,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'branch_id'              => 'required|exists:branches,id',
            'department_id'          => 'nullable|exists:departments,id',
            'purchase_requisition_id'=> 'nullable|exists:purchase_requisitions,id',
            'expected_delivery_date' => 'nullable|date|after:today',
            'delivery_address'       => 'nullable|string|max:1000',
            'vat_percentage'         => 'nullable|numeric|min:0|max:100',
            'freight_charges'        => 'nullable|numeric|min:0',
            'discount_amount'        => 'nullable|numeric|min:0',
            'payment_terms'          => 'nullable|string|max:100',
            'payment_terms_days'     => 'nullable|integer|min:0|max:365',
            'terms_conditions'       => 'nullable|string',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.item_id'        => 'required|exists:items,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.specifications' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($data, $request) {
                $po = PurchaseOrder::create([
                    ...$data,
                    'subtotal'   => 0,
                    'vat_amount' => 0,
                    'total_amount'=> 0,
                    'created_by' => auth()->id(),
                ]);

                foreach ($data['items'] as $itemData) {
                    $item = Item::find($itemData['item_id']);
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'item_id'           => $itemData['item_id'],
                        'purchase_requisition_item_id' => $itemData['pr_item_id'] ?? null,
                        'item_name'         => $item->name,
                        'unit'              => $item->unit,
                        'quantity'          => $itemData['quantity'],
                        'unit_price'        => $itemData['unit_price'],
                        'specifications'    => $itemData['specifications'] ?? null,
                    ]);
                }

                $po->recalculateTotals();
                $po->save();

                // Mark PR as closed if linked
                if (!empty($data['purchase_requisition_id'])) {
                    PurchaseRequisition::where('id', $data['purchase_requisition_id'])
                        ->update(['status' => 'closed', 'purchase_order_id' => $po->id]);
                }
            });

            return redirect()->route('tenant.purchase-orders.index')
                ->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            Log::error('PO creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create Purchase Order.');
        }
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'vendor', 'branch', 'department',
            'requisition:id,pr_number',
            'items.item:id,name,code',
            'creator:id,name',
            'updater:id,name',
            'sender:id,name',
        ]);

        return Inertia::render('Tenant/Procurement/PurchaseOrders/PO_Show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_if(
            !in_array($purchaseOrder->status, ['draft']),
            403, 'Only draft purchase orders can be edited.'
        );

        $purchaseOrder->load(['vendor', 'branch', 'department', 'items.item']);

        return Inertia::render('Tenant/Procurement/PurchaseOrders/PO_Edit', [
            'purchaseOrder' => $purchaseOrder,
            'vendors'       => Vendor::active()->select('id', 'name', 'code', 'payment_terms_days', 'email')->orderBy('name')->get(),
            'branches'      => Branch::select('id', 'name')->orderBy('name')->get(),
            'departments'   => Department::select('id', 'name')->orderBy('name')->get(),
            'items'         => Item::active()->select('id', 'name', 'code', 'unit', 'current_price')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status !== 'draft', 403, 'Cannot edit a non-draft PO.');

        $data = $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'branch_id'              => 'required|exists:branches,id',
            'department_id'          => 'nullable|exists:departments,id',
            'expected_delivery_date' => 'nullable|date',
            'delivery_address'       => 'nullable|string|max:1000',
            'vat_percentage'         => 'nullable|numeric|min:0|max:100',
            'freight_charges'        => 'nullable|numeric|min:0',
            'discount_amount'        => 'nullable|numeric|min:0',
            'payment_terms'          => 'nullable|string|max:100',
            'payment_terms_days'     => 'nullable|integer|min:0|max:365',
            'terms_conditions'       => 'nullable|string',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.item_id'        => 'required|exists:items,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.specifications' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder->update([...$data, 'updated_by' => auth()->id()]);
            $purchaseOrder->items()->delete();

            foreach ($data['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id'           => $itemData['item_id'],
                    'item_name'         => $item->name,
                    'unit'              => $item->unit,
                    'quantity'          => $itemData['quantity'],
                    'unit_price'        => $itemData['unit_price'],
                    'specifications'    => $itemData['specifications'] ?? null,
                ]);
            }

            $purchaseOrder->recalculateTotals();
            $purchaseOrder->save();
        });

        return redirect()->route('tenant.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status !== 'draft', 403, 'Only draft POs can be deleted.');
        $purchaseOrder->delete();
        return redirect()->route('tenant.purchase-orders.index')
            ->with('success', 'Purchase Order deleted.');
    }

    /**
     * Send PO to vendor via email
     */
    public function send(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status !== 'draft', 403, 'PO already sent.');
        abort_if(!$purchaseOrder->vendor->email, 422, 'Vendor has no email address.');

        $purchaseOrder->update([
            'status'   => 'sent',
            'sent_at'  => now(),
            'sent_by'  => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // TODO: Queue email with PDF attachment
        // Mail::to($purchaseOrder->vendor->email)->queue(new PurchaseOrderMail($purchaseOrder));

        return back()->with('success', 'Purchase Order sent to vendor.');
    }

    /**
     * Cancel a PO
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if(in_array($purchaseOrder->status, ['received', 'closed', 'cancelled']), 403);

        $purchaseOrder->update([
            'status'     => 'cancelled',
            'notes'      => ($purchaseOrder->notes ? $purchaseOrder->notes . "\n" : '') . 'Cancelled: ' . ($request->reason ?? 'No reason given'),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase Order cancelled.');
    }

    /**
     * Get approved PRs available for PO creation
     */
    public function approvedPRs(): \Illuminate\Http\JsonResponse
    {
        $prs = PurchaseRequisition::where('status', 'approved')
            ->whereNull('purchase_order_id')
            ->select('id', 'pr_number', 'total_amount', 'purpose')
            ->with('branch:id,name', 'department:id,name')
            ->latest()
            ->get();

        return response()->json($prs);
    }
}