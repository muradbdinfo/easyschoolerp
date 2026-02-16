<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get first tenant
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->warn('No tenants found. Run TenantSeeder first.');
            return;
        }

        // Create test users for different roles
        $users = [
            [
                'tenant_id' => $tenant->id,
                'name' => 'John Doe',
                'email' => 'john@test.com',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'department' => 'Science',
                'branch' => 'Senior',
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Jane Smith',
                'email' => 'jane@test.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => 'Administration',
                'branch' => 'Main',
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Mike Wilson',
                'email' => 'mike@test.com',
                'password' => Hash::make('password'),
                'role' => 'procurement_officer',
                'department' => 'Finance',
                'branch' => 'Main',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            $this->command->info("Created user: {$userData['email']} ({$userData['role']})");
        }

        // FIXED: Only create notifications if NotificationService exists
        if (class_exists('App\Services\NotificationService')) {
            try {
                $this->createSampleNotifications($users);
            } catch (\Exception $e) {
                $this->command->warn('Could not create notifications: ' . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info('Test users created successfully!');
        $this->command->info('All passwords: password');
    }

    /**
     * Create sample notifications for testing
     * FIXED: Don't reference specific record IDs that may not exist
     */
    private function createSampleNotifications(array $users): void
    {
        $notificationService = app(\App\Services\NotificationService::class);
        
        // Get the first user
        $user = User::where('email', 'john@test.com')->first();
        
        if (!$user) {
            return;
        }

        // Create generic notifications that don't depend on other records
        $notificationService->systemNotification(
            $user,
            'Welcome to School ERP',
            'Your account has been created successfully. Explore the system!',
            '/dashboard'
        );

        // FIXED: Only create specific notifications if the records exist
        if (class_exists('App\Models\PurchaseRequisition')) {
            $requisition = \App\Models\PurchaseRequisition::first();
            if ($requisition) {
                $notificationService->approvalRequest(
                    $user,
                    'New Purchase Requisition Pending',
                    "PR-{$requisition->pr_number} requires your approval.",
                    "/procurement/requisitions/{$requisition->id}",
                    'PurchaseRequisition',
                    $requisition->id
                );
            }
        }

        if (class_exists('App\Models\Asset')) {
            $asset = \App\Models\Asset::first();
            if ($asset) {
                $notificationService->assetAlert(
                    $user,
                    'Maintenance Due Soon',
                    "Asset {$asset->asset_tag} maintenance is due in 3 days",
                    "/assets/{$asset->id}"
                );
            }
        }
    }
}