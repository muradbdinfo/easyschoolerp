<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequisition;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Item;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequisitionController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    public function index(Request $request): Response
    {
        $query = PurchaseRequisition::with([
            'user:id,name,email',
            'department:id,name',
            'branch:id,name',
            'items'
        ]);

        if ($request->boolean('my_requisitions')) {
            $query->myRequisitions(auth()->id());
        }
        if ($request->boolean('pending_my_approval')) {
            $query->pendingMyApproval(auth()->id());
        }
        if ($request->filled('status')) {
            is_array($request->status)
                ? $query->whereIn('status', $request->status)
                : $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('pr_number', 'like', "%{$s}%")
                  ->orWhere('purpose', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->where('pr_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('pr_date', '<=', $request->date_to);
        }
        if ($request->filled('amount_min')) {
            $query->where('total_amount', '>=', $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $query->where('total_amount', '<=', $request->amount_max);
        }

        $query->orderBy(
            $request->get('sort_field', 'pr_date'),
            $request->get('sort_order', 'desc')
        );

        $requisitions = $query->paginate($request->get('per_page', 15));

        $summary = [
            'total'     => PurchaseRequisition::count(),
            'draft'     => PurchaseRequisition::where('status', 'draft')->count(),
            'submitted' => PurchaseRequisition::whereIn('status', [
                               'submitted', 'pending_level_1', 'pending_level_2', 'pending_level_3'
                           ])->count(),
            'approved'  => PurchaseRequisition::where('status', 'approved')->count(),
            'rejected'  => PurchaseRequisition::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Tenant/Procurement/Requisitions/Index', [
            'requisitions' => $requisitions,
            'departments'  => Department::active()->get(['id', 'name']),
            'branches'     => Branch::active()->get(['id', 'name']),
            'summary'      => $summary,
            'canCreate'    => true,
            'filters'      => $request->only([
                'search', 'status', 'priority', 'department_id',
                'date_from', 'date_to', 'amount_min', 'amount_max',
                'my_requisitions', 'pending_my_approval',
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenant/Procurement/Requisitions/Create', [
            'departments' => Department::active()->get(['id', 'name', 'code']),
            'branches'    => Branch::active()->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        \Log::info('PR Store Request', $request->except('attachments'));

        try {
            $validated = $request->validate([
                'department_id'    => 'required|exists:departments,id',
                'branch_id'        => 'required|exists:branches,id',
                'required_by_date' => 'required|date',
                'priority'         => 'required|in:low,medium,high,urgent',
                'purpose'          => 'required|string|min:20',
                'justification'    => 'nullable|string',
                'notes'            => 'nullable|string',
                'status'           => 'required|in:draft,submitted',
                'items'            => 'required|json',
                'attachments'      => 'nullable|array',
                'attachments.*'    => 'file|max:5120',
            ]);

            DB::beginTransaction();

            $pr = PurchaseRequisition::create([
                'user_id'          => auth()->id(),
                'pr_date'          => now()->toDateString(),
                'department_id'    => $validated['department_id'],
                'branch_id'        => $validated['branch_id'],
                'required_by_date' => $validated['required_by_date'],
                'priority'         => $validated['priority'],
                'purpose'          => $validated['purpose'],
                'justification'    => $validated['justification'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'status'           => $validated['status'],
            ]);

            $items = json_decode($validated['items'], true);

            if (empty($items) || !is_array($items)) {
                throw new \Exception('No items provided');
            }

            foreach ($items as $itemData) {
                if (empty($itemData['item_id'])) {
                    throw new \Exception('Item ID missing');
                }
                $item = Item::findOrFail($itemData['item_id']);
                $pr->items()->create([
                    'item_id'              => $item->id,
                    'item_code'            => $item->code,
                    'item_name'            => $item->name,
                    'item_description'     => $item->description ?? '',
                    'unit'                 => $item->unit,
                    'quantity'             => $itemData['quantity'] ?? 1,
                    'estimated_unit_price' => $itemData['estimated_unit_price'] ?? $item->current_price ?? 0,
                    'specifications'       => $itemData['specifications'] ?? null,
                    'notes'                => $itemData['notes'] ?? null,
                ]);
            }

            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path          = $file->store('requisitions/' . $pr->id, 'public');
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

            if ($validated['status'] === 'submitted') {
                $this->approvalService->submitForApproval($pr);
            }

            DB::commit();

            return redirect()
                ->route('tenant.procurement.requisitions.show', $pr->id)
                ->with('success', $validated['status'] === 'submitted'
                    ? 'Purchase requisition submitted for approval successfully'
                    : 'Purchase requisition saved as draft successfully'
                );

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Validation failed: ' . collect($e->errors())->flatten()->implode(', '));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PR Store Error: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Failed to create purchase requisition: ' . $e->getMessage());
        }
    }

    public function show(PurchaseRequisition $requisition): Response
    {
        $requisition->load([
            'user', 'department', 'branch', 'items.item',
            'level1Approver', 'level2Approver', 'level3Approver',
            'finalApprover', 'rejectedBy',
        ]);

        return Inertia::render('Tenant/Procurement/Requisitions/Show', [
            'requisition' => $requisition,
            'canApprove'  => $this->approvalService->canApprove($requisition, auth()->user()),
        ]);
    }

    public function edit(PurchaseRequisition $requisition): Response
    {
        if (!$requisition->is_editable) {
            abort(403, 'This requisition cannot be edited');
        }
        if ($requisition->user_id !== auth()->id()) {
            abort(403, 'You can only edit your own requisitions');
        }

        $requisition->load('items');

        return Inertia::render('Tenant/Procurement/Requisitions/Edit', [
            'requisition' => $requisition,
            'departments' => Department::active()->get(['id', 'name', 'code']),
            'branches'    => Branch::active()->get(['id', 'name', 'code']),
        ]);
    }

    public function update(Request $request, PurchaseRequisition $requisition)
    {
        if (!$requisition->is_editable) {
            return redirect()->back()->with('error', 'This requisition cannot be edited');
        }

        try {
            DB::beginTransaction();

            $requisition->update($request->only([
                'department_id', 'branch_id', 'required_by_date',
                'priority', 'purpose', 'justification', 'notes', 'status',
            ]));

            $requisition->items()->delete();

            $items = json_decode($request->input('items', '[]'), true);
            foreach ($items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                $requisition->items()->create([
                    'item_id'              => $item->id,
                    'item_code'            => $item->code,
                    'item_name'            => $item->name,
                    'item_description'     => $item->description ?? '',
                    'unit'                 => $item->unit,
                    'quantity'             => $itemData['quantity'] ?? 1,
                    'estimated_unit_price' => $itemData['estimated_unit_price'] ?? $item->current_price ?? 0,
                    'specifications'       => $itemData['specifications'] ?? null,
                    'notes'                => $itemData['notes'] ?? null,
                ]);
            }

            if ($request->hasFile('attachments')) {
                $attachments = $requisition->attachments ?? [];
                foreach ($request->file('attachments') as $file) {
                    $path          = $file->store('requisitions/' . $requisition->id, 'public');
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

            if ($request->status === 'submitted' && $requisition->getOriginal('status') === 'draft') {
                $this->approvalService->submitForApproval($requisition);
            }

            DB::commit();

            return redirect()
                ->route('tenant.procurement.requisitions.show', $requisition->id)
                ->with('success', 'Purchase requisition updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy(PurchaseRequisition $requisition)
    {
        if (!$requisition->is_editable) {
            return redirect()->back()->with('error', 'Only draft requisitions can be deleted');
        }
        if ($requisition->user_id !== auth()->id()) {
            return redirect()->back()->with('error', 'You can only delete your own requisitions');
        }

        $requisition->items()->delete();
        $requisition->delete();

        return redirect()
            ->route('tenant.procurement.requisitions.index')
            ->with('success', 'Requisition deleted successfully');
    }

    public function deleteAttachment(PurchaseRequisition $requisition, $index)
    {
        if (!$requisition->is_editable) {
            return response()->json(['error' => 'Cannot delete attachment'], 403);
        }
        $attachments = $requisition->attachments ?? [];
        if (isset($attachments[$index])) {
            Storage::disk('public')->delete($attachments[$index]['path']);
            unset($attachments[$index]);
            $requisition->attachments = array_values($attachments);
            $requisition->save();
        }
        return response()->json(['success' => true]);
    }

    public function searchItems(Request $request)
    {
        $query = $request->get('query', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        try {
            $items = Item::where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'description', 'unit', 'current_price', 'category_id']);

            return response()->json($items->map(fn($i) => [
                'id'          => $i->id,
                'code'        => $i->code,
                'name'        => $i->name,
                'description' => $i->description,
                'unit'        => $i->unit,
                'unit_price'  => (float) ($i->current_price ?? 0),
                'category'    => $i->category_id,
            ]));
        } catch (\Exception $e) {
            \Log::error('Item search error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function autosave(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'id'            => 'nullable|integer',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id'     => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            if ($request->filled('id')) {
                $pr = PurchaseRequisition::findOrFail($request->id);
                if (!$pr->is_editable || $pr->user_id !== auth()->id()) {
                    return response()->json(['error' => 'Cannot save'], 403);
                }
                $pr->update($request->only([
                    'department_id', 'branch_id', 'required_by_date',
                    'priority', 'purpose', 'justification', 'notes',
                ]));
            } else {
                if (!$request->filled('department_id') && !$request->filled('branch_id')) {
                    return response()->json(['success' => false, 'message' => 'Nothing to save yet']);
                }
                $pr = PurchaseRequisition::create(array_merge(
                    $request->only([
                        'department_id', 'branch_id', 'required_by_date',
                        'priority', 'purpose', 'justification', 'notes',
                    ]),
                    [
                        'user_id' => auth()->id(),
                        'status'  => 'draft',
                        'pr_date' => now()->toDateString(),
                        'purpose' => $request->get('purpose') ?: 'Draft',
                    ]
                ));
            }
            return response()->json(['success' => true, 'pr_id' => $pr->id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}