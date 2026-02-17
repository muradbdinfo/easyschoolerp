<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrConvertedToPoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequisition $pr,
        public int $poId,
        public ?string $poNumber = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PR Converted to PO: #{$this->pr->pr_number}")
            ->line("Purchase requisition has been converted to a Purchase Order.")
            ->line("PR Number: {$this->pr->pr_number}")
            ->line("PO Number: {$this->poNumber}")
            ->action('View PO', url("/purchase-orders/{$this->poId}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'user_id' => $notifiable instanceof User ? $notifiable->id : $this->pr->user_id,
            'type' => 'pr_converted',
            'title' => "PR #{$this->pr->pr_number} converted to PO",
            'message' => "Purchase requisition #{$this->pr->pr_number} has been converted to purchase order " . ($this->poNumber ?? "#{$this->poId}"),
            'action_url' => "/purchase-orders/{$this->poId}",
            'related_type' => PurchaseRequisition::class,
            'related_id' => $this->pr->id,
        ];
    }
}