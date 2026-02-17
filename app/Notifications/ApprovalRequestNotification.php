<?php

namespace App\Notifications;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseRequisition $pr,
        public ?string $level = null,
        public ?string $message = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $levelText = $this->level ? "Level {$this->level}" : "Approval";
        
        return (new MailMessage)
            ->subject("{$levelText} Required: PR #{$this->pr->pr_number}")
            ->line("A purchase requisition requires your {$levelText} approval.")
            ->line("PR Number: {$this->pr->pr_number}")
            ->line("Department: {$this->pr->department?->name}")
            ->line("Total Amount: {$this->pr->total_amount} BDT")
            ->line("Priority: {$this->pr->priority}")
            ->action('Review PR', url("/purchase-requisitions/{$this->pr->id}"))
            ->line($this->message ?? 'Please review and approve/reject this request.');
    }

    /**
     * Custom format for your existing notifications table
     */
    public function toDatabase(object $notifiable): array
    {
        $levelText = $this->level ? "Level {$this->level}" : "Approval";
        
        return [
            'user_id' => $this->getApproverId($notifiable),
            'type' => 'approval_request',
            'title' => "PR #{$this->pr->pr_number} requires {$levelText} approval",
            'message' => $this->message ?? "Purchase requisition {$this->pr->pr_number} from {$this->pr->department?->name} requires your approval. Total: {$this->pr->total_amount} BDT",
            'action_url' => "/purchase-requisitions/{$this->pr->id}",
            'related_type' => PurchaseRequisition::class,
            'related_id' => $this->pr->id,
        ];
    }

    /**
     * Get approver user ID
     */
    protected function getApproverId(object $notifiable): int
    {
        if ($notifiable instanceof User) {
            return $notifiable->id;
        }
        
        // If notifiable is the PR, find the department head or default to PR user
        return $this->pr->department?->head_id ?? $this->pr->user_id;
    }
}