<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequisitionRequest;
use App\Models\PurchaseRequisition;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Item;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequisitionController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    /**
     * Display a listing of purchase requisitions
     */
    public function index(Request $request): Response
    {
        $query = PurchaseRequisition::with([
            'user:id,name,email',
            'department:id,name',
            'branch:id,name',
            'items'
        ]);

        // Filter: My PRs
        if ($request->boolean('my_requisitions')) {
            $query->myRequisitions(auth()->id());
        }

        // Filter: Pending my approval
        if ($request->boolean('pending_my_approval')) {
            $query->pendingMyApproval(auth()->id());
        }

        // Filter: Status
        if ($request->filled('status')) {
            if (is_array($request->status)) {
                $query->whereIn('status', $request->status);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter: Date range
        if ($request->filled('date_from')) {
            $query->where('pr_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('pr_date', '<=', $request->date_to);
        }

        // Filter: Amount range
        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $query->where('total_amount', '<=', $request->amount_max);
        }

        // Search by PR number
        if ($request->filled('search')) {
            $query->where('pr_number', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sortField = $request->get('sort_field', 'pr_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Paginate
        $requisitions = $query->paginate($request->get('per_page', 15));

        return Inertia::render('Tenant/Procurement/Requisitions/Index', [
            'requisitions' => $requisitions,
            'filters' => $request->only(['my_requisitions', 'pending_my_approval', 'status', 'date_from', 'date_to', 'amount_min', 'amount_max', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new purchase requisition
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Procurement/Requisitions/Create', [
            'departments' => Department::active()->get(['id', 'name', 'code']),
            'branches' => Branch::active()->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created purchase requisition
     */
    public function store(StorePurchaseRequisitionRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create PR
            $pr = PurchaseRequisition::create($request->except('items', 'attachments'));

            // Add items
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                $pr->items()->create([
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'item_description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => $itemData['quantity'],
                    'estimated_unit_price' => $itemData['estimated_unit_price'],
                    'specifications' => $itemData['specifications'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('requisitions/' . $pr->id, 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
                $pr->attachments = $attachments;
                $pr->save();
            }

            // If status is submitted, initiate approval workflow
            if ($request->status === 'submitted') {
                $this->approvalService->submitForApproval($pr);
            }

            DB::commit();

            return redirect()
                ->route('tenant.procurement.requisitions.show', $pr->id)
                ->with('success', $request->status === 'submitted' 
                    ? 'Purchase requisition submitted for approval successfully'
                    : 'Purchase requisition saved as draft successfully'
                );

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create purchase requisition: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase requisition
     */
    public function show(PurchaseRequisition $requisition): Response
    {
        $requisition->load([
            'user',
            'department',
            'branch',
            'items.item',
            'level1Approver',
            'level2Approver',
            'level3Approver',
            'finalApprover',
            'rejectedBy',
        ]);

        return Inertia::render('Tenant/Procurement/Requisitions/Show', [
            'requisition' => $requisition,
            'canApprove' => $this->approvalService->canApprove($requisition, auth()->user()),
        ]);
    }

    /**
     * Show the form for editing
     */
    public function edit(PurchaseRequisition $requisition): Response
    {
        // Only drafts can be edited
        if (!$requisition->is_editable) {
            abort(403, 'This requisition cannot be edited');
        }

        // Only owner can edit
        if ($requisition->user_id !== auth()->id()) {
            abort(403, 'You can only edit your own requisitions');
        }

        $requisition->load('items');

        return Inertia::render('Tenant/Procurement/Requisitions/Edit', [
            'requisition' => $requisition,
            'departments' => Department::active()->get(['id', 'name', 'code']),
            'branches' => Branch::active()->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Update the specified purchase requisition
     */
    public function update(StorePurchaseRequisitionRequest $request, PurchaseRequisition $requisition)
    {
        // Only drafts can be updated
        if (!$requisition->is_editable) {
            return redirect()
                ->back()
                ->with('error', 'This requisition cannot be edited');
        }

        try {
            DB::beginTransaction();

            // Update PR
            $requisition->update($request->except('items', 'attachments'));

            // Delete existing items
            $requisition->items()->delete();

            // Add new items
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                $requisition->items()->create([
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'item_description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => $itemData['quantity'],
                    'estimated_unit_price' => $itemData['estimated_unit_price'],
                    'specifications' => $itemData['specifications'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Handle new file attachments
            if ($request->hasFile('attachments')) {
                $attachments = $requisition->attachments ?? [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('requisitions/' . $requisition->id, 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }
                $requisition->attachments = $attachments;
                $requisition->save();
            }

            // If status changed to submitted, initiate approval
            if ($request->status === 'submitted' && $requisition->status === 'draft') {
                $this->approvalService->submitForApproval($requisition);
            }

            DB::commit();

            return redirect()
                ->route('tenant.procurement.requisitions.show', $requisition->id)
                ->with('success', 'Purchase requisition updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update purchase requisition: ' . $e->getMessage());
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(PurchaseRequisition $requisition, $index)
    {
        if (!$requisition->is_editable) {
            return response()->json(['error' => 'Cannot delete attachment'], 403);
        }

        $attachments = $requisition->attachments ?? [];
        
        if (isset($attachments[$index])) {
            // Delete file from storage
            Storage::disk('public')->delete($attachments[$index]['path']);
            
            // Remove from array
            unset($attachments[$index]);
            
            // Reindex array
            $requisition->attachments = array_values($attachments);
            $requisition->save();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Search items for autocomplete
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('query');
        
        $items = Item::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'code', 'name', 'description', 'unit', 'unit_price', 'category']);

        return response()->json($items);
    }

    /**
     * Auto-save draft
     */
    public function autosave(Request $request)
    {
        // Simple validation
        $request->validate([
            'id' => 'nullable|exists:purchase_requisitions,id',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            if ($request->has('id')) {
                // Update existing draft
                $pr = PurchaseRequisition::findOrFail($request->id);
                
                if (!$pr->is_editable || $pr->user_id !== auth()->id()) {
                    return response()->json(['error' => 'Cannot save'], 403);
                }
                
                $pr->update($request->only([
                    'department_id',
                    'branch_id',
                    'required_by_date',
                    'priority',
                    'purpose',
                    'justification',
                    'notes',
                ]));
            } else {
                // Create new draft
                $pr = PurchaseRequisition::create(array_merge(
                    $request->only([
                        'department_id',
                        'branch_id',
                        'required_by_date',
                        'priority',
                        'purpose',
                        'justification',
                        'notes',
                    ]),
                    [
                        'user_id' => auth()->id(),
                        'status' => 'draft',
                    ]
                ));
            }

            return response()->json([
                'success' => true,
                'pr_id' => $pr->id,
                'message' => 'Draft saved',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}