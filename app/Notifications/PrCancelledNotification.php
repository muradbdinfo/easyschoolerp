<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequisition $pr,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PR Cancelled: #{$this->pr->pr_number}")
            ->line("Purchase requisition has been cancelled.")
            ->line("PR Number: {$this->pr->pr_number}")
            ->line($this->reason ? "Reason: {$this->reason}" : 'No reason provided')
            ->action('View PR', url("/purchase-requisitions/{$this->pr->id}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'user_id' => $notifiable instanceof User ? $notifiable->id : $this->pr->user_id,
            'type' => 'pr_cancelled',
            'title' => "PR #{$this->pr->pr_number} cancelled",
            'message' => $this->reason ?? "Purchase requisition #{$this->pr->pr_number} has been cancelled",
            'action_url' => "/purchase-requisitions/{$this->pr->id}",
            'related_type' => PurchaseRequisition::class,
            'related_id' => $this->pr->id,
        ];
    }
}