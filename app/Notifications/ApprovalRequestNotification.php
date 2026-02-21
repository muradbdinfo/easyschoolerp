<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent to the approver when a PR is submitted / advances to their level.
 *
 * ✅ FIX: Removed 'database' from via().
 *
 * WHY: Laravel's 'database' channel writes to a table that needs these columns:
 *   id (UUID), notifiable_type, notifiable_id, type, data (JSON), read_at
 *
 * Our `notifications` table schema is completely different:
 *   id (bigint), user_id (FK), type (varchar 50), title, message, action_url,
 *   related_type, related_id, read_at
 *
 * Keeping 'database' in via() causes a SQL error on every approval because
 * it tries to INSERT with uuid/notifiable_type/data columns that don't exist.
 *
 * The in-app notification is handled separately by:
 *   ApprovalService::createNotification() → \App\Models\Notification::create()
 * That method writes directly to our `notifications` table with the correct columns.
 *
 * toArray() is kept here in case you switch to a different notification driver
 * later, but it is NOT called in the current setup.
 */
class ApprovalRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PurchaseRequisition $requisition
    ) {}

    // ✅ FIX: 'mail' only — 'database' removed
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Approval Required: {$this->requisition->pr_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A purchase requisition requires your approval.")
            ->line("**PR Number:** {$this->requisition->pr_number}")
            ->line("**Purpose:** {$this->requisition->purpose}")
            ->line("**Amount:** " . number_format($this->requisition->total_amount, 2) . " BDT")
            ->action('Review Requisition', route('tenant.requisitions.show', $this->requisition->id))
            ->line('Please review and take action at your earliest convenience.');
    }

    // Kept for reference / future use — NOT called in current setup
    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'approval_request',
            'title'        => 'Approval Required',
            'message'      => "PR {$this->requisition->pr_number} requires your approval.",
            'pr_id'        => $this->requisition->id,
            'pr_number'    => $this->requisition->pr_number,
            'total_amount' => $this->requisition->total_amount,
            'action_url'   => route('tenant.requisitions.show', $this->requisition->id),
        ];
    }
}