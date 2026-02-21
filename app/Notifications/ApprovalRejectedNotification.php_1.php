<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PurchaseRequisition $requisition,
        public readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("❌ Rejected: {$this->requisition->pr_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your purchase requisition has been rejected.")
            ->line("**PR Number:** {$this->requisition->pr_number}")
            ->line("**Purpose:** {$this->requisition->purpose}")
            ->line("**Rejection Reason:** {$this->reason}")
            ->action('View Requisition', route('tenant.requisitions.show', $this->requisition->id))
            ->line('You may create a new requisition addressing the feedback above.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'approval_rejected',
            'title'      => 'Requisition Rejected',
            'message'    => "Your PR {$this->requisition->pr_number} was rejected: {$this->reason}",
            'pr_id'      => $this->requisition->id,
            'pr_number'  => $this->requisition->pr_number,
            'reason'     => $this->reason,
            'action_url' => route('tenant.requisitions.show', $this->requisition->id),
        ];
    }
}