<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,           // 1. Tenant first
            ItemCategorySeeder::class,     // 2. Lookup tables
            DepartmentSeeder::class,       // 3. Departments (head_id null for now)
            BranchSeeder::class,           // 4. Branches (head_id null for now)
            UserSeeder::class,             // 5. Users (needs tenant/dept/branch IDs)
            HeadAssignmentSeeder::class,   // 6. Assign heads (needs users)
            ProcurementTestDataSeeder::class, // 7. Vendors & items
            NotificationSeeder::class,     // 8. Notifications (needs users)
        ]);
    }
}