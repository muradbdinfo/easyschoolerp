<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Green Valley International School',
                'subdomain' => 'greenvalley',
                'database_name' => 'tenant_greenvalley',
                'status' => 'active',
                'plan' => 'professional',
                'active_modules' => ['procurement', 'assets'],
                'mrr' => 100.00,
                'contact_name' => 'Dr. Sarah Johnson',
                'contact_email' => 'sarah@greenvalley.edu',
                'contact_phone' => '+880 1712-345678',
                'trial_ends_at' => null,
                'activated_at' => now()->subDays(30),
            ],
            [
                'name' => 'Sunshine Academy',
                'subdomain' => 'sunshine',
                'database_name' => 'tenant_sunshine',
                'status' => 'trial',
                'plan' => 'basic',
                'active_modules' => ['procurement'],
                'mrr' => 0,
                'contact_name' => 'Mr. Ahmed Rahman',
                'contact_email' => 'ahmed@sunshine.edu',
                'contact_phone' => '+880 1812-345678',
                'trial_ends_at' => now()->addDays(15),
                'activated_at' => null,
            ],
            [
                'name' => 'Oxford International School',
                'subdomain' => 'oxford',
                'database_name' => 'tenant_oxford',
                'status' => 'active',
                'plan' => 'enterprise',
                'active_modules' => ['procurement', 'assets'],
                'mrr' => 200.00,
                'contact_name' => 'Ms. Emily Chen',
                'contact_email' => 'emily@oxford.edu',
                'contact_phone' => '+880 1912-345678',
                'trial_ends_at' => null,
                'activated_at' => now()->subDays(60),
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::create($tenant);
        }
    }
}