<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use App\Models\ApprovalPolicy;
use App\Models\User;
use App\Notifications\ApprovalRequestNotification;
use App\Notifications\ApprovalCompletedNotification;
use App\Notifications\ApprovalRejectedNotification;

class ApprovalService
{
    /**
     * Load active policies for this PR's tenant, ordered by level.
     */
    private function getPolicies(PurchaseRequisition $pr): \Illuminate\Support\Collection
    {
        $tenantId = $pr->user->tenant_id ?? null;

        // Use tenant-specific policies if they exist, else global defaults
        $hasTenant = ApprovalPolicy::where('tenant_id', $tenantId)
            ->where('is_active', true)->exists();

        return ApprovalPolicy::when($hasTenant,
                fn($q) => $q->where('tenant_id', $tenantId),
                fn($q) => $q->whereNull('tenant_id')
            )
            ->where('is_active', true)
            ->orderBy('level')
            ->get();
    }

    /**
     * Find first active user in this tenant with any of the given roles.
     */
    private function resolveUser(array $roles, int $tenantId): ?int
    {
        return User::where('tenant_id', $tenantId)
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Assign approvers from policies and set required_approval_levels.
     * Level 1 is ALWAYS the PR creator's department head (strict).
     * Levels 2–5 resolved from approval_policies role_name.
     */
    public function assignApprovers(PurchaseRequisition $pr): void
    {
        $tenantId = $pr->user->tenant_id;
        $policies = $this->getPolicies($pr);

        $pr->required_approval_levels = $policies->count();

        foreach ($policies as $policy) {
            $level = $policy->level;

            if ($level === 1) {
                // Strictly use the department head of the PR creator's department
                $pr->level_1_approver_id = $pr->department->head_id ?? $pr->user_id;
            } else {
                $roles      = (array) $policy->role_name;
                $approverId = $this->resolveUser($roles, $tenantId);
                $pr->{"level_{$level}_approver_id"} = $approverId;
            }

            $pr->{"level_{$level}_status"} = 'pending';
        }

        $pr->save();
    }

    /**
     * Submit PR for approval — starts at level 1.
     */
    public function submitForApproval(PurchaseRequisition $pr): void
    {
        $this->assignApprovers($pr);

        $pr->status                = 'pending_level_1';
        $pr->current_approval_level = 1;
        $pr->save();

        $this->notifyApprover($pr, 1);
    }

    /**
     * Approve current level — advance to next or mark fully approved.
     */
    public function approve(PurchaseRequisition $pr, User $approver, ?string $comments = null): void
    {
        $level = $pr->current_approval_level;

        $pr->{"level_{$level}_approved_at"} = now();
        $pr->{"level_{$level}_comments"}    = $comments;
        $pr->{"level_{$level}_status"}      = 'approved';

        $this->addHistory($pr, $level, 'approved', $approver, $comments);

        $totalLevels = $pr->required_approval_levels ?? $level;

        if ($level < $totalLevels) {
            $next               = $level + 1;
            $pr->current_approval_level = $next;
            $pr->status         = "pending_level_{$next}";
            $pr->save();
            $this->notifyApprover($pr, $next);
        } else {
            $pr->status            = 'approved';
            $pr->final_approved_at = now();
            $pr->final_approved_by = $approver->id;
            $pr->save();

            $this->notify($pr->user, new ApprovalCompletedNotification($pr),
                "PR {$pr->pr_number} has been fully approved.");
        }
    }

    /**
     * Reject at current level.
     */
    public function reject(PurchaseRequisition $pr, User $approver, string $reason): void
    {
        $level = $pr->current_approval_level;

        $pr->{"level_{$level}_approved_at"} = now();
        $pr->{"level_{$level}_comments"}    = $reason;
        $pr->{"level_{$level}_status"}      = 'rejected';
        $pr->status           = 'rejected';
        $pr->rejection_reason = $reason;
        $pr->rejected_at      = now();
        $pr->rejected_by      = $approver->id;

        $this->addHistory($pr, $level, 'rejected', $approver, $reason);
        $pr->save();

        $this->notify($pr->user, new ApprovalRejectedNotification($pr, $reason),
            "PR {$pr->pr_number} was rejected: {$reason}");
    }

    /**
     * Check if user can approve the current level.
     * Supports levels 1–5.
     */
    public function canApprove(PurchaseRequisition $pr, User $user): bool
    {
        $level = $pr->current_approval_level;

        if (!$level) return false;

        // Must be one of the pending statuses (covers all 5 levels)
        $pendingStatuses = array_map(fn($l) => "pending_level_{$l}", range(1, 5));
        if (!in_array($pr->status, $pendingStatuses)) return false;

        // Current level status must still be pending
        if ($pr->{"level_{$level}_status"} !== 'pending') return false;

        // User must be the assigned approver
        return (int) $pr->{"level_{$level}_approver_id"} === (int) $user->id;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function notifyApprover(PurchaseRequisition $pr, int $level): void
    {
        $approver = $pr->{"level{$level}Approver"} ?? null;
        if (!$approver) {
            // Lazy-load if not loaded
            $approverId = $pr->{"level_{$level}_approver_id"};
            $approver   = $approverId ? User::find($approverId) : null;
        }

        if (!$approver) return;

        $this->notify($approver, new ApprovalRequestNotification($pr),
            "PR {$pr->pr_number} requires your approval (Level {$level}).");
    }

    private function notify(User $user, $notification, string $message): void
    {
        try {
            $user->notify($notification);
        } catch (\Exception $e) {
            \Log::warning('Notify mail failed: ' . $e->getMessage());
        }

        try {
            \App\Models\Notification::create([
                'user_id'      => $user->id,
                'type'         => 'approval_request',
                'title'        => 'Approval Required',
                'message'      => $message,
                'action_url'   => route('tenant.requisitions.show',
                                    request()->route('requisition') ?? ''),
                'related_type' => PurchaseRequisition::class,
                'related_id'   => null,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Notify DB failed: ' . $e->getMessage());
        }
    }

    private function addHistory(PurchaseRequisition $pr, int $level, string $action,
                                 User $approver, ?string $comments): void
    {
        $history   = $pr->approval_history ?? [];
        $history[] = [
            'level'         => $level,
            'action'        => $action,
            'approver_id'   => $approver->id,
            'approver_name' => $approver->name,
            'comments'      => $comments,
            'timestamp'     => now()->toDateTimeString(),
        ];
        $pr->approval_history = $history;
    }
}