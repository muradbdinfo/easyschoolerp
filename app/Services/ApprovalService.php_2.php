<?php

namespace App\Services;

use App\Models\PurchaseRequisition;
use App\Models\User;
use App\Notifications\ApprovalRequestNotification;
use App\Notifications\ApprovalCompletedNotification;
use App\Notifications\ApprovalRejectedNotification;

class ApprovalService
{
    const LEVEL_1_THRESHOLD = 50000;
    const LEVEL_2_THRESHOLD = 200000;
    const LEVEL_3_THRESHOLD = 500000;

    public function determineApprovalLevels(PurchaseRequisition $pr): int
    {
        $amount = $pr->total_amount;

        if ($amount <= self::LEVEL_1_THRESHOLD) {
            return 1;
        } elseif ($amount <= self::LEVEL_2_THRESHOLD) {
            return 2;
        } else {
            return 3;
        }
    }

    public function assignApprovers(PurchaseRequisition $pr): void
    {
        $levels = $this->determineApprovalLevels($pr);
        $pr->required_approval_levels = $levels;

        // ✅ FIX 1: eager-load department before accessing head_id
        // Original: $pr->department->head_id — crashes if relation not loaded
        if (!$pr->relationLoaded('department')) {
            $pr->load('department');
        }

        $deptHeadId = $pr->department?->head_id ?? null;

        // Level 1: Department Head
        $pr->level_1_approver_id = $deptHeadId ?? $pr->user_id;
        $pr->level_1_status      = 'pending';

        if ($levels >= 2) {
            // ✅ FIX 2: fallback to level_1_approver_id NOT $pr->user_id
            // Original fallback was user_id — requester approving own PR is a security hole
            $pr->level_2_approver_id = $this->getVPOrPrincipal() ?? $pr->level_1_approver_id;
            $pr->level_2_status      = 'pending';
        }

        if ($levels >= 3) {
            // ✅ FIX 3: fallback to level_2_approver_id NOT $pr->user_id
            $pr->level_3_approver_id = $this->getAdminApprover() ?? $pr->level_2_approver_id;
            $pr->level_3_status      = 'pending';
        }

        $pr->save();
    }

    public function submitForApproval(PurchaseRequisition $pr): void
    {
        $this->assignApprovers($pr);

        $pr->status                 = 'pending_level_1';
        $pr->current_approval_level = 1;
        $pr->save();

        try {
            if ($pr->level1Approver) {
                $pr->level1Approver->notify(new ApprovalRequestNotification($pr));
            }
            // ✅ FIX 5+6: updated createNotification signature with explicit type/title
            $this->createNotification(
                $pr,
                $pr->level1Approver,
                'approval_request',
                'Approval Required',
                "Purchase requisition {$pr->pr_number} requires your approval"
            );
        } catch (\Exception $e) {
            \Log::warning('submitForApproval notify failed: ' . $e->getMessage());
        }
    }

    public function approve(PurchaseRequisition $pr, User $approver, ?string $comments = null): void
    {
        $currentLevel = $pr->current_approval_level;

        $pr->{"level_{$currentLevel}_approved_at"} = now();
        $pr->{"level_{$currentLevel}_comments"}    = $comments;
        $pr->{"level_{$currentLevel}_status"}      = 'approved';

        $this->addToApprovalHistory($pr, $currentLevel, 'approved', $approver, $comments);

        if ($currentLevel < $pr->required_approval_levels) {
            $pr->current_approval_level = $currentLevel + 1;
            $pr->status                 = "pending_level_{$pr->current_approval_level}";
            $pr->save();

            try {
                $nextApprover = $pr->{"level{$pr->current_approval_level}Approver"};
                if ($nextApprover) {
                    $nextApprover->notify(new ApprovalRequestNotification($pr));
                    // ✅ FIX 6: explicit type/title/message
                    $this->createNotification(
                        $pr,
                        $nextApprover,
                        'approval_request',
                        'Approval Required',
                        "Purchase requisition {$pr->pr_number} requires your approval (Level {$pr->current_approval_level})"
                    );
                }
            } catch (\Exception $e) {
                \Log::warning('approve next-level notify failed: ' . $e->getMessage());
            }
        } else {
            $pr->status            = 'approved';
            $pr->final_approved_at = now();
            $pr->final_approved_by = $approver->id;
            $pr->save();

            try {
                $pr->user->notify(new ApprovalCompletedNotification($pr));
                // ✅ FIX 5+6: was hardcoded 'approval_request' type — now 'approval_completed'
                $this->createNotification(
                    $pr,
                    $pr->user,
                    'approval_completed',
                    'Requisition Approved',
                    "Your purchase requisition {$pr->pr_number} has been approved"
                );
            } catch (\Exception $e) {
                \Log::warning('approve completed notify failed: ' . $e->getMessage());
            }
        }
    }

    public function reject(PurchaseRequisition $pr, User $approver, string $reason): void
    {
        $currentLevel = $pr->current_approval_level;

        $pr->{"level_{$currentLevel}_approved_at"} = now();
        $pr->{"level_{$currentLevel}_comments"}    = $reason;
        $pr->{"level_{$currentLevel}_status"}      = 'rejected';
        $pr->status                                = 'rejected';
        $pr->rejection_reason                      = $reason;
        $pr->rejected_at                           = now();
        $pr->rejected_by                           = $approver->id;

        $this->addToApprovalHistory($pr, $currentLevel, 'rejected', $approver, $reason);
        $pr->save();

        try {
            $pr->user->notify(new ApprovalRejectedNotification($pr, $reason));
            // ✅ FIX 5+6: was hardcoded 'approval_request' type — now 'approval_rejected'
            $this->createNotification(
                $pr,
                $pr->user,
                'approval_rejected',
                'Requisition Rejected',
                "Your purchase requisition {$pr->pr_number} has been rejected: {$reason}"
            );
        } catch (\Exception $e) {
            \Log::warning('reject notify failed: ' . $e->getMessage());
        }
    }

    public function canApprove(PurchaseRequisition $pr, User $user): bool
    {
        $currentLevel = $pr->current_approval_level;
        $approverId   = $pr->{"level_{$currentLevel}_approver_id"};

        // ✅ FIX 4: (int) cast on both sides prevents strict string vs int mismatch
        // Original: $approverId === $user->id — fails if DB returns "5" and id is 5
        return (int) $approverId === (int) $user->id
            && $pr->{"level_{$currentLevel}_status"} === 'pending'
            && in_array($pr->status, ['pending_level_1', 'pending_level_2', 'pending_level_3']);
    }

    private function addToApprovalHistory(
        PurchaseRequisition $pr,
        int $level,
        string $action,
        User $approver,
        ?string $comments
    ): void {
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

    // ✅ FIX 5: added explicit $type and $title parameters
    // Original: hardcoded 'approval_request' / 'Approval Required' for all 3 event types
    // Now: completed → 'approval_completed', rejected → 'approval_rejected'
    private function createNotification(
        PurchaseRequisition $pr,
        ?User $user,
        string $type,
        string $title,
        string $message
    ): void {
        if (!$user) return;

        try {
            \App\Models\Notification::create([
                'user_id'      => $user->id,
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'action_url'   => route('tenant.requisitions.show', $pr->id),
                'related_type' => PurchaseRequisition::class,
                'related_id'   => $pr->id,
            ]);
        } catch (\Exception $e) {
            \Log::warning('createNotification failed: ' . $e->getMessage());
        }
    }

    // ✅ FIX 7: role names now match the actual users table enum
    // Enum: ['admin', 'principal', 'vp', 'dept_head', 'teacher', 'staff']
    // Original searched: 'vice_principal', 'managing_director', 'deputy_managing_director'
    //   — NONE of these exist in the enum, so level 2 approver was never found
    private function getVPOrPrincipal(): ?int
    {
        try {
            $tenantId = auth()->user()?->tenant_id;

            return User::where('tenant_id', $tenantId)
                ->whereIn('role', [
                    'principal',  // ✅ matches enum
                    'vp',         // ✅ matches enum (was 'vice_principal' — wrong)
                ])
                ->where('is_active', true)
                ->first()?->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ✅ FIX 8: renamed getBoardApprover() → getAdminApprover()
    // Original searched: 'rector', 'director_admin', 'managing_director'
    //   — NONE exist in enum, so level 3 approver was NEVER found
    // Now uses 'admin' role which exists in enum
    private function getAdminApprover(): ?int
    {
        try {
            $tenantId = auth()->user()?->tenant_id;

            return User::where('tenant_id', $tenantId)
                ->where('role', 'admin')  // ✅ matches enum
                ->where('is_active', true)
                ->first()?->id;
        } catch (\Exception $e) {
            return null;
        }
    }
}