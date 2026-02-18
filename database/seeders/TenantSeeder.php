<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'name'           => 'Presidency International School',
            'subdomain'      => 'presidency',
            'database_name'  => 'tenant_presidency',
            'status'         => 'active',
            'plan'           => 'enterprise',
            'active_modules' => ['procurement', 'assets'],
            'mrr'            => 200.00,
            'contact_name'   => 'Jasim Uddin',
            'contact_email'  => 'director@easyschool.local',
            'contact_phone'  => '+880-1700-000000',
            'trial_ends_at'  => null,
            'activated_at'   => now()->subDays(60),
        ]);

        $this->command->info('✓ Tenant: Presidency International School');
    }
}