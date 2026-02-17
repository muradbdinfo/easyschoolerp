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
        // Get all categories with relationships
        $categories = ItemCategory::with(['parent', 'children'])
            ->whereNull('parent_id') // Only root categories
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Transform to format expected by frontend (recursive tree structure)
        $transformedCategories = $this->transformToTree($categories);

        return Inertia::render('Tenant/Procurement/Categories/Index', [
            'categories' => $this->getAllCategoriesFlat(), // For dropdown
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
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Set default values
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = 0;
        }

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
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Prevent circular reference
        if (isset($validated['parent_id'])) {
            if ($validated['parent_id'] == $category->id) {
                return back()->with('error', 'A category cannot be its own parent.');
            }

            // Check if new parent is a descendant of current category
            if ($this->isDescendant($category->id, $validated['parent_id'])) {
                return back()->with('error', 'Cannot move category under its own descendant.');
            }
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
        $itemCount = \DB::table('items')->where('category_id', $category->id)->count();
        
        if ($itemCount > 0) {
            return back()->with('error', "Cannot delete category with {$itemCount} existing items.");
        }

        // Check if category has children
        $childrenCount = ItemCategory::where('parent_id', $category->id)->count();
        
        if ($childrenCount > 0) {
            return back()->with('error', "Cannot delete category with {$childrenCount} subcategories.");
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Get categories for dropdown/select.
     */
    public function list(Request $request)
    {
        $query = ItemCategory::query()->where('is_active', true);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->orderBy('name')->get();

        return response()->json($categories);
    }

    /**
     * Get all categories in flat array format.
     */
    private function getAllCategoriesFlat()
    {
        return ItemCategory::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'description' => $category->description,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ];
            });
    }

    /**
     * Transform categories to tree structure for TreeTable.
     */
    private function transformToTree($categories)
    {
        return $categories->map(function($category) {
            return [
                'key' => $category->id,
                'data' => [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'description' => $category->description,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                ],
                'children' => $this->transformToTree($category->children),
            ];
        });
    }

    /**
     * Check if a category is a descendant of another.
     */
    private function isDescendant($categoryId, $potentialParentId)
    {
        $category = ItemCategory::find($potentialParentId);
        
        while ($category) {
            if ($category->id == $categoryId) {
                return true;
            }
            $category = $category->parent;
        }
        
        return false;
    }
}