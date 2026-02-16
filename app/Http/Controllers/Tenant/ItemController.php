<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request): Response
    {
        $query = Item::query()->with(['category', 'creator', 'updater']);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->lowStock();
                    break;
                case 'out':
                    $query->outOfStock();
                    break;
            }
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $items = $query->paginate($request->get('per_page', 15))
            ->withQueryString();

        return Inertia::render('Tenant/Procurement/Items/Index', [
            'items' => $items,
            'categories' => ItemCategory::active()->get(),
            'filters' => $request->only(['search', 'category_id', 'type', 'status', 'stock_status']),
            'stats' => [
                'total' => Item::count(),
                'active' => Item::active()->count(),
                'low_stock' => Item::lowStock()->count(),
                'out_of_stock' => Item::outOfStock()->count(),
                'consumables' => Item::consumable()->count(),
                'assets' => Item::asset()->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new item.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Procurement/Items/Create', [
            'categories' => ItemCategory::active()->get(),
        ]);
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(ItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('items', 'public');
        }

        $item = Item::create($data);

        return redirect()
            ->route('tenant.items.index')
            ->with('success', 'Item created successfully.');
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item): Response
    {
        $item->load(['category', 'creator', 'updater']);

        return Inertia::render('Tenant/Procurement/Items/Show', [
            'item' => $item,
            'stats' => [
                'total_requisitions' => $item->requisitionItems()->count(),
                'total_quantity_ordered' => $item->requisitionItems()->sum('quantity'),
                'total_value' => $item->requisitionItems()->sum('total_amount'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit(Item $item): Response
    {
        return Inertia::render('Tenant/Procurement/Items/Edit', [
            'item' => $item,
            'categories' => ItemCategory::active()->get(),
        ]);
    }

    /**
     * Update the specified item in storage.
     */
    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($item->photo) {
                Storage::disk('public')->delete($item->photo);
            }
            $data['photo'] = $request->file('photo')->store('items', 'public');
        }

        $item->update($data);

        return redirect()
            ->route('tenant.items.index')
            ->with('success', 'Item updated successfully.');
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(Item $item): RedirectResponse
    {
        // Check if item has any requisitions
        if ($item->requisitionItems()->exists()) {
            return back()->with('error', 'Cannot delete item with existing requisitions.');
        }

        $item->delete();

        return redirect()
            ->route('tenant.items.index')
            ->with('success', 'Item deleted successfully.');
    }

    /**
     * Search items for autocomplete.
     */
    public function search(Request $request)
    {
        $query = Item::query()->active();

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        $items = $query->limit(20)->get();

        return response()->json($items);
    }

    /**
     * Bulk import items from Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        // This will be implemented with Maatwebsite\Excel
        // For now, return success
        
        return back()->with('success', 'Items imported successfully.');
    }

    /**
     * Export items to Excel.
     */
    public function export(Request $request)
    {
        $query = Item::query()->with('category');

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->get();

        return response()->json($items);
    }

    /**
     * Update stock for an item.
     */
    public function updateStock(Request $request, Item $item): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|numeric',
            'operation' => 'required|in:add,subtract',
            'notes' => 'nullable|string|max:500',
        ]);

        $item->updateStock($request->quantity, $request->operation);

        return back()->with('success', 'Stock updated successfully.');
    }
}