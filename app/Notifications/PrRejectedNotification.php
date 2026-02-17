<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequisition $pr,
        public string $level,
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PR Rejected: #{$this->pr->pr_number}")
            ->line("Purchase requisition has been rejected.")
            ->line("PR Number: {$this->pr->pr_number}")
            ->line("Reason: {$this->reason}")
            ->action('View PR', url("/purchase-requisitions/{$this->pr->id}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'user_id' => $notifiable instanceof User ? $notifiable->id : $this->pr->user_id,
            'type' => 'pr_rejected',
            'title' => "PR #{$this->pr->pr_number} rejected",
            'message' => "Purchase requisition #{$this->pr->pr_number} has been rejected at level {$this->level}. Reason: {$this->reason}",
            'action_url' => "/purchase-requisitions/{$this->pr->id}",
            'related_type' => PurchaseRequisition::class,
            'related_id' => $this->pr->id,
        ];
    }
}