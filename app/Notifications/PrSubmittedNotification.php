<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PurchaseRequisition $pr) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PR Submitted: #{$this->pr->pr_number}")
            ->line("A new purchase requisition has been submitted.")
            ->line("PR Number: {$this->pr->pr_number}")
            ->line("Requester: {$this->pr->user?->name}")
            ->action('View PR', url("/purchase-requisitions/{$this->pr->id}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'user_id' => $notifiable instanceof User ? $notifiable->id : $this->pr->user_id,
            'type' => 'pr_submitted',
            'title' => "PR #{$this->pr->pr_number} submitted for approval",
            'message' => "Purchase requisition #{$this->pr->pr_number} has been submitted by {$this->pr->user?->name} from {$this->pr->department?->name}. Total: {$this->pr->total_amount} BDT",
            'action_url' => "/purchase-requisitions/{$this->pr->id}",
            'related_type' => PurchaseRequisition::class,
            'related_id' => $this->pr->id,
        ];
    }
}