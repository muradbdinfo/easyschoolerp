<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AssetCategory::with('parent')
            ->withCount('assets');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 1 : 0);
        }

        $categories = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Assets/Categories/Index', [
            'categories' => $categories,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'parent_id'             => 'nullable|exists:asset_categories,id',
            'depreciation_method'   => 'required|in:slm,wdv,none',
            'depreciation_rate'     => 'required|numeric|min:0|max:100',
            'useful_life_years'     => 'required|integer|min:1|max:100',
            'residual_value_percent'=> 'required|numeric|min:0|max:100',
            'status'                => 'boolean',
        ]);

        $data['created_by'] = auth()->id();

        AssetCategory::create($data);

        return back()->with('success', 'Asset category created successfully.');
    }

    public function update(Request $request, AssetCategory $assetCategory): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
            'parent_id'             => 'nullable|exists:asset_categories,id',
            'depreciation_method'   => 'required|in:slm,wdv,none',
            'depreciation_rate'     => 'required|numeric|min:0|max:100',
            'useful_life_years'     => 'required|integer|min:1|max:100',
            'residual_value_percent'=> 'required|numeric|min:0|max:100',
            'status'                => 'boolean',
        ]);

        $assetCategory->update($data);

        return back()->with('success', 'Asset category updated successfully.');
    }

    public function destroy(AssetCategory $assetCategory): RedirectResponse
    {
        if ($assetCategory->assets()->exists()) {
            return back()->with('error', 'Cannot delete category with existing assets.');
        }

        $assetCategory->delete();

        return back()->with('success', 'Asset category deleted.');
    }
}