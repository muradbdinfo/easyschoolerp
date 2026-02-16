<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get unread notifications for notification bell
     */
    public function getUnread()
    {
        $notifications = $this->notificationService->getUnread(Auth::user(), 10);
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Auth::user()->unread_notification_count,
        ]);
    }

    /**
     * Get all notifications (paginated)
     */
    public function index()
    {
        $notifications = $this->notificationService->getAll(Auth::user());
        
        return inertia('Tenant/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        // Authorization check
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }
        
        $this->notificationService->markAsRead($notification);
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(Auth::user());
        
        return response()->json(['success' => true]);
    }

    /**
     * Delete notification
     */
    public function destroy(Notification $notification)
    {
        // Authorization check
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }
        
        $this->notificationService->delete($notification);
        
        return response()->json(['success' => true]);
    }
}