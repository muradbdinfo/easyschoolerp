<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ItemCategoryController extends Controller
{
    /**
     * Display a listing of item categories.
     */
    public function index(): Response
    {
        $categories = ItemCategory::with(['parent', 'children'])
            ->rootCategories()
            ->get();

        return Inertia::render('Tenant/Procurement/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:item_categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ItemCategory::create($validated);

        return back()->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, ItemCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:item_categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Prevent circular reference
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            return back()->with('error', 'A category cannot be its own parent.');
        }

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(ItemCategory $category): RedirectResponse
    {
        // Check if category has items
        if ($category->items()->exists()) {
            return back()->with('error', 'Cannot delete category with existing items.');
        }

        // Check if category has children
        if ($category->hasChildren()) {
            return back()->with('error', 'Cannot delete category with subcategories.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Get categories for dropdown/select.
     */
    public function list(Request $request)
    {
        $query = ItemCategory::query()->active();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->get()->map(function($category) {
            return [
                'id' => $category->id,
                'name' => $category->full_name,
                'parent_id' => $category->parent_id,
            ];
        });

        return response()->json($categories);
    }
}