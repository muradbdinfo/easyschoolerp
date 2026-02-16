<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        $notifications = [
            [
                'type' => 'approval_request',
                'title' => 'New Approval Request',
                'message' => 'Purchase requisition PR-2024-001 requires your approval. Amount: $1,250.00',
                'action_url' => '/procurement/requisitions/1',
            ],
            [
                'type' => 'approval_completed',
                'title' => 'Requisition Approved',
                'message' => 'Your purchase requisition PR-2024-002 has been approved by Principal.',
                'action_url' => '/procurement/requisitions/2',
            ],
            [
                'type' => 'asset_alert',
                'title' => 'Maintenance Due',
                'message' => 'Asset AS-001 (Dell Laptop) has maintenance due in 3 days.',
                'action_url' => '/assets/register/1',
            ],
            [
                'type' => 'po_created',
                'title' => 'Purchase Order Created',
                'message' => 'PO-2024-015 has been created from your requisition PR-2024-003.',
                'action_url' => '/procurement/orders/15',
            ],
            [
                'type' => 'system_info',
                'title' => 'System Update',
                'message' => 'A new feature has been added: Asset QR Code Scanning is now available!',
                'action_url' => '/help/whats-new',
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'action_url' => $notification['action_url'],
            ]);
        }

        $this->command->info('✅ Created ' . count($notifications) . ' test notifications for user: ' . $user->name);
    }
}