<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ApprovalPolicy extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'level',
        'min_amount',
        'max_amount',
        'role_name',   // JSON array: ["PO_DMD","PO_MD"] or ["PO_STAFF"]
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active'  => 'boolean',
        'level'      => 'integer',
        'sort_order' => 'integer',
        'role_name'  => 'array',   // always decoded as array
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Tenant policies override globals.
     * If tenant has ANY active policies → use theirs only.
     * Otherwise → fall back to global defaults (tenant_id = null).
     */
    public static function forTenant(int $tenantId): Builder
    {
        $hasTenant = static::where('tenant_id', $tenantId)->where('is_active', true)->exists();

        return $hasTenant
            ? static::where('tenant_id', $tenantId)->where('is_active', true)
            : static::whereNull('tenant_id')->where('is_active', true);
    }

    /**
     * Policies that cover a given amount.
     * max_amount = null means no upper limit.
     */
    public function scopeForAmount(Builder $q, float $amount): Builder
    {
        return $q->where('min_amount', '<=', $amount)
                 ->where(function (Builder $q2) use ($amount) {
                     $q2->whereNull('max_amount')
                        ->orWhere('max_amount', '>=', $amount);
                 });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Find the first active user in this tenant that matches any role in role_name[].
     *
     * Example: role_name = ["PO_DMD","PO_MD"]
     *   → finds first user with role PO_DMD or PO_MD
     *
     * Returns user ID or null if no match found.
     */
    public function resolveApprover(int $tenantId): ?int
    {
        $roles = (array) $this->role_name; // always an array thanks to cast

        return User::where('tenant_id', $tenantId)
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->orderBy('id')               // deterministic — same user every time
            ->value('id');
    }

    /**
     * Human-readable roles string for UI display.
     * e.g. ["PO_DMD","PO_MD"] → "PO_DMD / PO_MD"
     */
    public function getRolesLabelAttribute(): string
    {
        return implode(' / ', (array) $this->role_name);
    }
}