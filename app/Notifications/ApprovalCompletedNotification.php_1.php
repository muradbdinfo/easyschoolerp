<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalCompletedNotification extends Notification
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
            ->subject("✅ Approved: {$this->requisition->pr_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! Your purchase requisition has been fully approved.")
            ->line("**PR Number:** {$this->requisition->pr_number}")
            ->line("**Purpose:** {$this->requisition->purpose}")
            ->line("**Approved Amount:** " . number_format($this->requisition->total_amount, 2) . " BDT")
            ->line("**Approved On:** " . now()->format('d M Y'))
            ->action('View Requisition', route('tenant.requisitions.show', $this->requisition->id))
            ->line('The procurement team will follow up with next steps.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'approval_completed',
            'title'        => 'Requisition Approved',
            'message'      => "Your PR {$this->requisition->pr_number} has been approved.",
            'pr_id'        => $this->requisition->id,
            'pr_number'    => $this->requisition->pr_number,
            'total_amount' => $this->requisition->total_amount,
            'action_url'   => route('tenant.requisitions.show', $this->requisition->id),
        ];
    }
}