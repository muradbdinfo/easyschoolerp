<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PurchaseRequisition $requisition
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'approval_request',
            'title'          => 'Approval Required',
            'message'        => "PR {$this->requisition->pr_number} requires your approval.",
            'pr_id'          => $this->requisition->id,
            'pr_number'      => $this->requisition->pr_number,
            'total_amount'   => $this->requisition->total_amount,
            'action_url'     => route('tenant.requisitions.show', $this->requisition->id),
        ];
    }
}