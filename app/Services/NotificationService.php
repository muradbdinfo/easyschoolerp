<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnread(User $user, int $limit = 10): Collection
    {
        return $user->notifications()
            ->unread()
            ->recent($limit)
            ->get();
    }

    /**
     * Get all notifications for a user
     */
    public function getAll(User $user, int $perPage = 15)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Delete notification
     */
    public function delete(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Create approval request notification
     */
    public function approvalRequest(
        User $approver,
        string $title,
        string $message,
        string $actionUrl,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): Notification {
        return $this->create(
            $approver,
            'approval_request',
            $title,
            $message,
            $actionUrl,
            $relatedType,
            $relatedId
        );
    }

    /**
     * Create approval completed notification
     */
    public function approvalCompleted(
        User $requester,
        string $title,
        string $message,
        string $actionUrl
    ): Notification {
        return $this->create(
            $requester,
            'approval_completed',
            $title,
            $message,
            $actionUrl
        );
    }

    /**
     * Create approval rejected notification
     */
    public function approvalRejected(
        User $requester,
        string $title,
        string $message,
        string $actionUrl
    ): Notification {
        return $this->create(
            $requester,
            'approval_rejected',
            $title,
            $message,
            $actionUrl
        );
    }

    /**
     * Create asset alert notification
     */
    public function assetAlert(
        User $user,
        string $title,
        string $message,
        string $actionUrl
    ): Notification {
        return $this->create(
            $user,
            'asset_alert',
            $title,
            $message,
            $actionUrl
        );
    }

    /**
     * Create system notification
     */
    public function systemNotification(
        User $user,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): Notification {
        return $this->create(
            $user,
            'system_info',
            $title,
            $message,
            $actionUrl
        );
    }
}