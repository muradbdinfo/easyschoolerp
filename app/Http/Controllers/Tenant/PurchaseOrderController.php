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

        if ($request->filled('search'))    { $query->search($request->search); }
        if ($request->filled('status'))    { $query->where('status', $request->status); }
        if ($request->filled('vendor_id')) { $query->where('vendor_id', $request->vendor_id); }
        if ($request->filled('branch_id')) { $query->where('branch_id', $request->branch_id); }
        if ($request->filled('date_from')) { $query->where('po_date', '>=', $request->date_from); }
        if ($request->filled('date_to'))   { $query->where('po_date', '<=', $request->date_to); }

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
            'vendor_id'               => 'required|integer|exists:vendors,id',
            'branch_id'               => 'required|integer|exists:branches,id',
            'department_id'           => 'nullable|integer|exists:departments,id',
            'purchase_requisition_id' => 'nullable|integer|exists:purchase_requisitions,id',
            'expected_delivery_date'  => 'nullable|date',
            'delivery_address'        => 'nullable|string|max:1000',
            'vat_percentage'          => 'nullable|numeric|min:0|max:100',
            'freight_charges'         => 'nullable|numeric|min:0',
            'discount_amount'         => 'nullable|numeric|min:0',
            'payment_terms'           => 'nullable|string|max:100',
            'payment_terms_days'      => 'nullable|integer|min:0|max:365',
            'terms_conditions'        => 'nullable|string',
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.item_id'         => 'nullable|integer|exists:items,id',
            'items.*.item_name'       => 'nullable|string|max:255',
            'items.*.unit'            => 'nullable|string|max:50',
            'items.*.quantity'        => 'required|numeric|min:0.01',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'items.*.specifications'  => 'nullable|string',
        ]);

        Log::info('[PO Store] Validation passed', [
            'vendor_id'   => $data['vendor_id'],
            'branch_id'   => $data['branch_id'],
            'items_count' => count($data['items']),
            'user_id'     => auth()->id(),
        ]);

        try {
            $newPoId = null;

            DB::transaction(function () use ($data, &$newPoId) {

                // 1. Create PO header via Eloquent (for po_number auto-generation)
                $po = PurchaseOrder::create([
                    'vendor_id'               => $data['vendor_id'],
                    'branch_id'               => $data['branch_id'],
                    'department_id'           => $data['department_id']           ?? null,
                    'purchase_requisition_id' => $data['purchase_requisition_id'] ?? null,
                    // Strip time — column is date not datetime
                    'expected_delivery_date'  => !empty($data['expected_delivery_date'])
                        ? \Carbon\Carbon::parse($data['expected_delivery_date'])->toDateString()
                        : null,
                    'delivery_address'        => $data['delivery_address']        ?? null,
                    'vat_percentage'          => $data['vat_percentage']          ?? 0,
                    'freight_charges'         => $data['freight_charges']         ?? 0,
                    'discount_amount'         => $data['discount_amount']         ?? 0,
                    'payment_terms'           => $data['payment_terms']           ?? null,
                    'payment_terms_days'      => $data['payment_terms_days']      ?? 30,
                    'terms_conditions'        => $data['terms_conditions']        ?? null,
                    'notes'                   => $data['notes']                   ?? null,
                    'subtotal'                => 0,
                    'vat_amount'              => 0,
                    'total_amount'            => 0,
                    'created_by'              => auth()->id(),
                ]);

                Log::info('[PO Store] PO header created', ['po_id' => $po->id, 'po_number' => $po->po_number]);

                // 2. Insert items via raw DB — bypasses Eloquent hooks and storedAs column conflict
                $subtotal = 0;
                foreach ($data['items'] as $idx => $itemData) {
                    $itemModel = !empty($itemData['item_id']) ? Item::find($itemData['item_id']) : null;
                    $qty   = (float) ($itemData['quantity']   ?? 1);
                    $price = (float) ($itemData['unit_price'] ?? 0);
                    $subtotal += $qty * $price;

                    DB::table('purchase_order_items')->insert([
                        'purchase_order_id'            => $po->id,
                        'item_id'                      => !empty($itemData['item_id']) ? (int) $itemData['item_id'] : null,
                        'purchase_requisition_item_id' => !empty($itemData['pr_item_id']) ? (int) $itemData['pr_item_id'] : null,
                        'item_name'                    => $itemModel?->name ?? ($itemData['item_name'] ?? 'Item'),
                        'unit'                         => $itemModel?->unit ?? ($itemData['unit']      ?? 'pcs'),
                        'quantity'                     => $qty,
                        'unit_price'                   => $price,
                        // total_price is MySQL storedAs — DO NOT INSERT
                        'received_quantity'            => 0,
                        'specifications'               => $itemData['specifications'] ?? null,
                        'created_at'                   => now(),
                        'updated_at'                   => now(),
                    ]);

                    Log::info("[PO Store] Item #{$idx} inserted OK");
                }

                // 3. Update totals via raw DB — bypasses Eloquent updating hook
                $vatPct   = (float) ($data['vat_percentage'] ?? 0);
                $vat      = round($subtotal * $vatPct / 100, 2);
                $freight  = (float) ($data['freight_charges'] ?? 0);
                $discount = (float) ($data['discount_amount'] ?? 0);
                $total    = $subtotal + $vat + $freight - $discount;

                DB::table('purchase_orders')
                    ->where('id', $po->id)
                    ->update([
                        'subtotal'     => $subtotal,
                        'vat_amount'   => $vat,
                        'total_amount' => $total,
                        'updated_at'   => now(),
                    ]);

                // 4. Close linked PR
                if (!empty($data['purchase_requisition_id'])) {
                    DB::table('purchase_requisitions')
                        ->where('id', $data['purchase_requisition_id'])
                        ->update([
                            'status'            => 'closed',
                            'purchase_order_id' => $po->id,
                            'updated_at'        => now(),
                        ]);
                }

                $newPoId = $po->id;
                Log::info('[PO Store] Transaction complete', ['po_id' => $newPoId]);
            });

            return redirect()
                ->route('tenant.purchase-orders.show', $newPoId)
                ->with('success', 'Purchase Order created successfully.');

        } catch (\Throwable $e) {
            Log::error('[PO Store] FAILED', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['debug_error' => $e->getMessage()])
                ->with('error', 'Failed: ' . $e->getMessage());
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
        abort_if($purchaseOrder->status !== 'draft', 403, 'Only draft purchase orders can be edited.');
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

        try {
            DB::transaction(function () use ($data, $purchaseOrder) {
                $purchaseOrder->update([
                    'vendor_id'              => $data['vendor_id'],
                    'branch_id'              => $data['branch_id'],
                    'department_id'          => $data['department_id']          ?? null,
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'delivery_address'       => $data['delivery_address']       ?? null,
                    'vat_percentage'         => $data['vat_percentage']         ?? 0,
                    'freight_charges'        => $data['freight_charges']        ?? 0,
                    'discount_amount'        => $data['discount_amount']        ?? 0,
                    'payment_terms'          => $data['payment_terms']          ?? null,
                    'payment_terms_days'     => $data['payment_terms_days']     ?? 30,
                    'terms_conditions'       => $data['terms_conditions']       ?? null,
                    'notes'                  => $data['notes']                  ?? null,
                    'updated_by'             => auth()->id(),
                ]);

                DB::table('purchase_order_items')->where('purchase_order_id', $purchaseOrder->id)->delete();

                $subtotal = 0;
                foreach ($data['items'] as $itemData) {
                    $itemModel = Item::find($itemData['item_id']);
                    $qty   = (float) $itemData['quantity'];
                    $price = (float) $itemData['unit_price'];
                    $subtotal += $qty * $price;

                    DB::table('purchase_order_items')->insert([
                        'purchase_order_id' => $purchaseOrder->id,
                        'item_id'           => $itemData['item_id'],
                        'item_name'         => $itemModel?->name ?? 'Item',
                        'unit'              => $itemModel?->unit ?? 'pcs',
                        'quantity'          => $qty,
                        'unit_price'        => $price,
                        'received_quantity' => 0,
                        'specifications'    => $itemData['specifications'] ?? null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }

                $vatPct = (float) ($data['vat_percentage'] ?? 0);
                $vat    = round($subtotal * $vatPct / 100, 2);
                $total  = $subtotal + $vat + (float)($data['freight_charges'] ?? 0) - (float)($data['discount_amount'] ?? 0);

                DB::table('purchase_orders')->where('id', $purchaseOrder->id)->update([
                    'subtotal'     => $subtotal,
                    'vat_amount'   => $vat,
                    'total_amount' => $total,
                    'updated_at'   => now(),
                ]);
            });

            return redirect()->route('tenant.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order updated.');
        } catch (\Throwable $e) {
            Log::error('[PO Update] FAILED: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status !== 'draft', 403, 'Only draft POs can be deleted.');
        $purchaseOrder->delete();
        return redirect()->route('tenant.purchase-orders.index')
            ->with('success', 'Purchase Order deleted.');
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->status !== 'draft', 403, 'PO already sent.');
        abort_if(!$purchaseOrder->vendor?->email, 422, 'Vendor has no email address.');

        $purchaseOrder->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'sent_by'    => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase Order sent to vendor.');
    }

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