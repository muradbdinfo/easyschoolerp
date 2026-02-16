<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // FIXED: Proper ordering - create tenants first, then categories, then test data, then users
        
        // Step 1: Create tenants (required for multi-tenant setup)
        $this->call(TenantSeeder::class);
        
        // Step 2: Create item categories (required before items can be created)
        $this->call(ItemCategorySeeder::class);
        
        // Step 3: Create test procurement data (vendors, items)
        $this->call(ProcurementTestDataSeeder::class);
        
        // Step 4: Create additional users (optional)
        // Uncomment if you want to create users with notifications
        // $this->call(UserSeeder::class);
        
        // Note: ProcurementTestDataSeeder already creates admin@test.com user
        // If you need additional test users, uncomment UserSeeder above
        
        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('Database seeding completed!');
        $this->command->info('=================================');
        $this->command->info('Test Credentials:');
        $this->command->info('Email: admin@test.com');
        $this->command->info('Password: password');
        $this->command->info('=================================');
    }
}