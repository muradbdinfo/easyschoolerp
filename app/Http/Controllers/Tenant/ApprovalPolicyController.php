<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ApprovalPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalPolicyController extends Controller
{
    public function index(): Response
    {
        $tenantId = auth()->user()->tenant_id;

        // Show tenant-specific policies if they exist, else show globals
        $hasTenantPolicies = ApprovalPolicy::where('tenant_id', $tenantId)->exists();

        $policies = ApprovalPolicy::when($hasTenantPolicies,
                fn ($q) => $q->where('tenant_id', $tenantId),
                fn ($q) => $q->whereNull('tenant_id')
            )
            ->orderBy('level')
            ->orderBy('min_amount')
            ->get();

        return Inertia::render('Tenant/Settings/ApprovalPolicies/Index', [
            'policies'         => $policies,
            'hasTenantPolicies'=> $hasTenantPolicies,
            'availableRoles'   => $this->availableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'level'      => 'required|integer|between:1,3',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'role_name'  => 'required|string|max:50',
            'sort_order' => 'integer|min:0',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        ApprovalPolicy::create($data);

        return back()->with('success', 'Approval policy created.');
    }

    public function update(Request $request, ApprovalPolicy $approvalPolicy): RedirectResponse
    {
        $this->authorizePolicy($approvalPolicy);

        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'level'      => 'required|integer|between:1,3',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'role_name'  => 'required|string|max:50',
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $approvalPolicy->update($data);

        return back()->with('success', 'Approval policy updated.');
    }

    public function destroy(ApprovalPolicy $approvalPolicy): RedirectResponse
    {
        $this->authorizePolicy($approvalPolicy);
        $approvalPolicy->delete();

        return back()->with('success', 'Approval policy deleted.');
    }

    /**
     * Copy global defaults to this tenant so they can customise without
     * affecting other tenants.
     */
    public function copyDefaults(): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Only copy if tenant has no policies yet
        if (ApprovalPolicy::where('tenant_id', $tenantId)->exists()) {
            return back()->with('error', 'You already have custom policies.');
        }

        $globals = ApprovalPolicy::whereNull('tenant_id')->where('is_active', true)->get();

        foreach ($globals as $global) {
            ApprovalPolicy::create([
                'tenant_id'  => $tenantId,
                'name'       => $global->name,
                'level'      => $global->level,
                'min_amount' => $global->min_amount,
                'max_amount' => $global->max_amount,
                'role_name'  => $global->role_name,
                'is_active'  => true,
                'sort_order' => $global->sort_order,
            ]);
        }

        return back()->with('success', 'Global defaults copied. You can now customise them.');
    }

    /**
     * Reset to global defaults by deleting all tenant-specific policies.
     */
    public function resetToDefaults(): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;
        ApprovalPolicy::where('tenant_id', $tenantId)->delete();

        return back()->with('success', 'Reset to global defaults.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizePolicy(ApprovalPolicy $policy): void
    {
        // Global policies (tenant_id = null) can only be edited by super admins
        // Tenant policies: must belong to current tenant
        if ($policy->tenant_id !== null) {
            abort_unless(
                $policy->tenant_id === auth()->user()->tenant_id,
                403
            );
        } else {
            // Only allow admins to touch global policies
            abort_unless(auth()->user()->role === 'admin', 403);
        }
    }

    /**
     * Return the list of roles that exist in this tenant's users table.
     * Used to populate the role_name dropdown in the UI.
     */
    private function availableRoles(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return \App\Models\User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->distinct()
            ->pluck('role')
            ->sort()
            ->values()
            ->toArray();
    }
}