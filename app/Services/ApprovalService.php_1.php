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

        // Level 1: Department Head (fallback to PR creator)
        $pr->level_1_approver_id = $pr->department->head_id ?? $pr->user_id;
        $pr->level_1_status = 'pending';

        if ($levels >= 2) {
            $pr->level_2_approver_id = $this->getVPOrPrincipal() ?? $pr->user_id;
            $pr->level_2_status = 'pending';
        }

        if ($levels >= 3) {
            $pr->level_3_approver_id = $this->getBoardApprover() ?? $pr->user_id;
            $pr->level_3_status = 'pending';
        }

        $pr->save();
    }

    public function submitForApproval(PurchaseRequisition $pr): void
    {
        $this->assignApprovers($pr);

        $pr->status = 'pending_level_1';
        $pr->current_approval_level = 1;
        $pr->save();

        try {
            if ($pr->level1Approver) {
                $pr->level1Approver->notify(new ApprovalRequestNotification($pr));
            }
            $this->createNotification($pr, $pr->level1Approver);
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
            $pr->status = "pending_level_{$pr->current_approval_level}";
            $pr->save();

            try {
                // Use property access not method call
                $nextApprover = $pr->{"level{$pr->current_approval_level}Approver"};
                if ($nextApprover) {
                    $nextApprover->notify(new ApprovalRequestNotification($pr));
                    $this->createNotification($pr, $nextApprover);
                }
            } catch (\Exception $e) {
                \Log::warning('approve next-level notify failed: ' . $e->getMessage());
            }
        } else {
            $pr->status          = 'approved';
            $pr->final_approved_at = now();
            $pr->final_approved_by = $approver->id;
            $pr->save();

            try {
                $pr->user->notify(new ApprovalCompletedNotification($pr));
                $this->createNotification($pr, $pr->user, 'Your purchase requisition has been approved');
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
        $pr->status           = 'rejected';
        $pr->rejection_reason = $reason;
        $pr->rejected_at      = now();
        $pr->rejected_by      = $approver->id;

        $this->addToApprovalHistory($pr, $currentLevel, 'rejected', $approver, $reason);
        $pr->save();

        try {
            $pr->user->notify(new ApprovalRejectedNotification($pr, $reason));
            $this->createNotification($pr, $pr->user, "Your purchase requisition has been rejected: {$reason}");
        } catch (\Exception $e) {
            \Log::warning('reject notify failed: ' . $e->getMessage());
        }
    }

    public function canApprove(PurchaseRequisition $pr, User $user): bool
    {
        $currentLevel = $pr->current_approval_level;
        $approverId   = $pr->{"level_{$currentLevel}_approver_id"};

        return $approverId === $user->id
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

private function createNotification(PurchaseRequisition $pr, ?User $user, ?string $customMessage = null): void
{
    if (!$user) return;

    $message = $customMessage ?? "Purchase requisition {$pr->pr_number} requires your approval";

    try {
        \App\Models\Notification::create([
            'user_id'      => $user->id,
            'type'         => 'approval_request',
            'title'        => 'Approval Required',
            'message'      => $message,
            'action_url'   => route('tenant.requisitions.show', $pr->id), // âœ… FIXED
            'related_type' => PurchaseRequisition::class,
            'related_id'   => $pr->id,
        ]);
    } catch (\Exception $e) {
        \Log::warning('createNotification failed: ' . $e->getMessage());
    }
}

    private function getVPOrPrincipal(): ?int
    {
        try {
            $tenantId = auth()->user()?->tenant_id;

            return User::where('tenant_id', $tenantId)
                ->whereIn('role', [
                    'vice_principal',
                    'principal',
                    'managing_director',
                    'deputy_managing_director',
                ])
                ->where('is_active', true)
                ->first()?->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getBoardApprover(): ?int
    {
        try {
            $tenantId = auth()->user()?->tenant_id;

            return User::where('tenant_id', $tenantId)
                ->whereIn('role', [
                    'rector',
                    'director_admin',
                    'managing_director',
                ])
                ->where('is_active', true)
                ->first()?->id;
        } catch (\Exception $e) {
            return null;
        }
    }
}