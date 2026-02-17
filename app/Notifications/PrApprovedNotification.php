<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequisition $pr,
        public string $level,
        public ?string $comments = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)
            ->subject("PR Approved: #{$this->pr->pr_number}")
            ->line("Purchase requisition approved at Level {$this->level}.");

        if ($this->pr->status === 'approved') {
            $msg->line("✅ FINAL APPROVAL COMPLETE");
        }

        return $msg
            ->line("PR Number: {$this->pr->pr_number}")
            ->action('View PR', url("/purchase-requisitions/{$this->pr->id}"))
            ->line($this->comments ? "Comments: {$this->comments}" : '');
    }

    public function toDatabase(object $notifiable): array
    {
        $isFinal = $this->pr->status === 'approved';
        
        return [
            'user_id' => $notifiable instanceof User ? $notifiable->id : $this->pr->user_id,
            'type' => 'pr_approved',
            'title' => $isFinal ? "PR #{$this->pr->pr_number} fully approved" : "PR #{$this->pr->pr_number} approved (Level {$this->level})",
            'message' => $this->comments ?? "Purchase requisition #{$this->pr->pr_number} has been " . ($isFinal ? "fully approved" : "approved at level {$this->level}") . ". Total: {$this->pr->total_amount} BDT",
            'action_url' => "/purchase-requisitions/{$this->pr->id}",
            'related_type' => PurchaseRequisition::class,
            'related_id' => $this->pr->id,
        ];
    }
}