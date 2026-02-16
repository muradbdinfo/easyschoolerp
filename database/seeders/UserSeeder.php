<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $notificationService = app(NotificationService::class);
        
        // Get first tenant
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->warn('No tenants found. Run TenantSeeder first.');
            return;
        }

        // Create test user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'department' => 'Science',
            'branch' => 'Senior',
        ]);

        // Create sample notifications
        $notificationService->approvalRequest(
            $user,
            'New Purchase Requisition Pending',
            'PR-2024-0001 requires your approval. Amount: $2,500',
            '/procurement/requisitions/1',
            'PurchaseRequisition',
            1
        );

        $notificationService->assetAlert(
            $user,
            'Maintenance Due Soon',
            'Asset AS-SEN-COMP-00045 maintenance is due in 3 days',
            '/assets/45'
        );

        $notificationService->systemNotification(
            $user,
            'Welcome to School ERP',
            'Your account has been created successfully. Explore the system!',
            '/dashboard'
        );

        $this->command->info('Test user created: john@test.com / password');
    }
}