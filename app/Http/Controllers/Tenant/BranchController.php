<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::with('head:id,name')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->status !== null, fn($q) => $q->where('is_active', $request->status))
            ->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tenant/Settings/Branches/Index', [
            'branches' => $branches,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Settings/Branches/Form', [
            'heads' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:20|unique:branches,code',
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'head_id'          => 'nullable|exists:users,id',
            'address'          => 'nullable|string',
            'city'             => 'nullable|string|max:100',
            'district'         => 'nullable|string|max:100',
            'postal_code'      => 'nullable|string|max:20',
            'country'          => 'nullable|string|max:100',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'fax'              => 'nullable|string|max:20',
            'established_date' => 'nullable|date',
            'is_active'        => 'boolean',
            'is_main_branch'   => 'boolean',
            'student_capacity' => 'nullable|integer|min:0',
            'staff_count'      => 'nullable|integer|min:0',
            'annual_budget'    => 'nullable|numeric|min:0',
        ]);

        // Only one main branch allowed
        if (!empty($data['is_main_branch']) && $data['is_main_branch']) {
            Branch::where('is_main_branch', true)->update(['is_main_branch' => false]);
        }

        Branch::create($data);

        return redirect()->route('tenant.settings.branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return Inertia::render('Tenant/Settings/Branches/Form', [
            'branch' => $branch,
            'heads'  => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'code'             => "required|string|max:20|unique:branches,code,{$branch->id}",
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'head_id'          => 'nullable|exists:users,id',
            'address'          => 'nullable|string',
            'city'             => 'nullable|string|max:100',
            'district'         => 'nullable|string|max:100',
            'postal_code'      => 'nullable|string|max:20',
            'country'          => 'nullable|string|max:100',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'fax'              => 'nullable|string|max:20',
            'established_date' => 'nullable|date',
            'is_active'        => 'boolean',
            'is_main_branch'   => 'boolean',
            'student_capacity' => 'nullable|integer|min:0',
            'staff_count'      => 'nullable|integer|min:0',
            'annual_budget'    => 'nullable|numeric|min:0',
        ]);

        if (!empty($data['is_main_branch']) && $data['is_main_branch']) {
            Branch::where('is_main_branch', true)
                ->where('id', '!=', $branch->id)
                ->update(['is_main_branch' => false]);
        }

        $branch->update($data);

        return redirect()->route('tenant.settings.branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->purchaseRequisitions()->exists()) {
            return back()->with('error', 'Cannot delete — branch has purchase requisitions.');
        }
        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }
}