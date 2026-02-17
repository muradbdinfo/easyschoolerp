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
        $query = Item::query()->with(['category']);

        // Load creator/updater if relationships exist
        if (method_exists(Item::class, 'creator')) {
            $query->with(['creator', 'updater']);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
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
                    $query->whereColumn('current_stock', '<=', 'min_stock_level');
                    break;
                case 'out':
                    $query->where('current_stock', '<=', 0);
                    break;
            }
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $items = $query->paginate($request->get('per_page', 15))
            ->withQueryString()
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'description' => $item->description,
                    'category' => $item->category ? [
                        'id' => $item->category->id,
                        'name' => $item->category->name,
                    ] : null,
                    'type' => $item->type,
                    'type_label' => ucfirst($item->type),
                    'unit' => $item->unit,
                    'current_price' => $item->current_price,
                    'current_stock' => $item->current_stock ?? 0,
                    'reorder_level' => $item->reorder_level ?? 0,
                    'min_stock_level' => $item->min_stock_level ?? 0,
                    'max_stock_level' => $item->max_stock_level ?? 0,
                    'status' => $item->status,
                    'status_badge' => [
                        'label' => ucfirst($item->status),
                        'severity' => $item->status === 'active' ? 'success' : 
                                    ($item->status === 'discontinued' ? 'danger' : 'warning')
                    ],
                    'stock_status' => $this->getStockStatus($item),
                    'photo_url' => $item->photo ? Storage::url($item->photo) : null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

        // Get categories
        $categories = ItemCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Tenant/Procurement/Items/Index', [
            'items' => $items,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'type', 'status', 'stock_status']),
            'stats' => [
                'total' => Item::count(),
                'active' => Item::where('status', 'active')->count(),
                'low_stock' => Item::whereColumn('current_stock', '<=', 'min_stock_level')->count(),
                'out_of_stock' => Item::where('current_stock', '<=', 0)->count(),
                'consumables' => Item::where('is_consumable', true)->count(),
                'assets' => Item::where('is_asset', true)->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new item.
     */
    public function create(): Response
    {
        $categories = ItemCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Tenant/Procurement/Items/Create', [
            'categories' => $categories,
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
        $item->load(['category']);

        // Load creator/updater if relationships exist
        if (method_exists($item, 'creator')) {
            $item->load(['creator', 'updater']);
        }

        // Add photo URL
        $itemData = $item->toArray();
        $itemData['photo_url'] = $item->photo ? Storage::url($item->photo) : null;
        $itemData['stock_status'] = $this->getStockStatus($item);

        return Inertia::render('Tenant/Procurement/Items/Show', [
            'item' => $itemData,
            'stats' => [
                'total_requisitions' => 0, // Will be implemented in Week 5
                'total_quantity_ordered' => 0,
                'total_value' => 0,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit(Item $item): Response
    {
        $categories = ItemCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $itemData = $item->toArray();
        $itemData['photo_url'] = $item->photo ? Storage::url($item->photo) : null;

        return Inertia::render('Tenant/Procurement/Items/Edit', [
            'item' => $itemData,
            'categories' => $categories,
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
        // Delete photo if exists
        if ($item->photo) {
            Storage::disk('public')->delete($item->photo);
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
        $query = Item::query()->where('status', 'active');

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
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
        
        return back()->with('success', 'Items import feature coming soon.');
    }

    /**
     * Export items to Excel.
     */
    public function export(Request $request)
    {
        $query = Item::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
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

        // Update stock based on operation
        if ($request->operation === 'add') {
            $item->current_stock = ($item->current_stock ?? 0) + $request->quantity;
        } else {
            $item->current_stock = max(0, ($item->current_stock ?? 0) - $request->quantity);
        }

        $item->save();

        return back()->with('success', 'Stock updated successfully.');
    }

    /**
     * Helper method to get stock status.
     */
    private function getStockStatus($item): array
    {
        $currentStock = $item->current_stock ?? 0;
        $reorderLevel = $item->reorder_level ?? 0;
        $minLevel = $item->min_stock_level ?? 0;
        $maxLevel = $item->max_stock_level ?? 0;

        if ($currentStock <= 0) {
            return ['label' => 'Out of Stock', 'severity' => 'danger'];
        } elseif ($currentStock <= $reorderLevel) {
            return ['label' => 'Reorder', 'severity' => 'warning'];
        } elseif ($currentStock <= $minLevel) {
            return ['label' => 'Low Stock', 'severity' => 'warning'];
        } elseif ($maxLevel > 0 && $currentStock >= $maxLevel) {
            return ['label' => 'Overstock', 'severity' => 'info'];
        } else {
            return ['label' => 'In Stock', 'severity' => 'success'];
        }
    }
}