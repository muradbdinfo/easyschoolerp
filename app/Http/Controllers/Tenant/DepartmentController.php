<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::with('head:id,name')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->status !== null, fn($q) => $q->where('is_active', $request->status))
            ->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tenant/Settings/Departments/Index', [
            'departments' => $departments,
            'filters'     => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Settings/Departments/Form', [
            'heads' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'               => 'required|string|max:20|unique:departments,code',
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'head_id'            => 'nullable|exists:users,id',
            'annual_budget'      => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'is_active'          => 'boolean',
            'phone'              => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'location'           => 'nullable|string',
        ]);

        Department::create($data);

        return redirect()->route('tenant.settings.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        return Inertia::render('Tenant/Settings/Departments/Form', [
            'department' => $department,
            'heads'      => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'code'               => "required|string|max:20|unique:departments,code,{$department->id}",
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'head_id'            => 'nullable|exists:users,id',
            'annual_budget'      => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'is_active'          => 'boolean',
            'phone'              => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'location'           => 'nullable|string',
        ]);

        $department->update($data);

        return redirect()->route('tenant.settings.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->purchaseRequisitions()->exists()) {
            return back()->with('error', 'Cannot delete — department has purchase requisitions.');
        }
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}