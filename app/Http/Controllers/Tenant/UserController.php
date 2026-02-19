<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = auth()->user()->tenant_id;

        $query = User::with(['branch:id,name', 'department:id,name'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
            );
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total'    => User::where('tenant_id', $tenantId)->count(),
            'active'   => User::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'inactive' => User::where('tenant_id', $tenantId)->where('is_active', false)->count(),
        ];

        return Inertia::render('Tenant/Settings/Users/Index', [
            'users'       => $users,
            'stats'       => $stats,
            'branches'    => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters'     => $request->only(['search', 'role', 'branch_id', 'status']),
            'roles'       => self::roleOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenant/Settings/Users/Form', [
            'user'        => null,
            'branches'    => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles'       => self::roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
            'role'          => ['required', Rule::in(array_keys(self::roleOptions()))],
            'branch_id'     => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'phone'         => 'nullable|string|max:30',
            'is_active'     => 'boolean',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['password']  = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        User::create($data);

        return redirect()
            ->route('tenant.settings.users.index')
            ->with('success', "User '{$data['name']}' created successfully.");
    }

    public function edit(User $user): Response
    {
        $this->authorizeUser($user);

        return Inertia::render('Tenant/Settings/Users/Form', [
            'user'        => $user->load(['branch:id,name', 'department:id,name']),
            'branches'    => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles'       => self::roleOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'      => 'nullable|string|min:8|confirmed',
            'role'          => ['required', Rule::in(array_keys(self::roleOptions()))],
            'branch_id'     => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'phone'         => 'nullable|string|max:30',
            'is_active'     => 'boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('tenant.settings.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('tenant.settings.users.index')
            ->with('success', "User '{$name}' deleted.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User '{$user->name}' {$status}.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeUser(User $user): void
    {
        abort_unless(
            $user->tenant_id === auth()->user()->tenant_id,
            403,
            'Unauthorized'
        );
    }

    public static function roleOptions(): array
    {
        return [
            'admin'     => 'Admin',
            'principal' => 'Principal',
            'vp'        => 'Vice Principal',
            'dept_head' => 'Department Head',
            'teacher'   => 'Teacher',
            'staff'     => 'Staff',
        ];
    }
}