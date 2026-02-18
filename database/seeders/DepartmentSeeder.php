<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $departments = [
            // Junior School
            [
                'code'               => 'DEPT-JR-ACA',
                'name'               => 'Junior School - Academy',
                'description'        => 'Academic department for Junior School.',
                'head_id'            => null,
                'annual_budget'      => 150000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 25000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'jr.academy@easyschool.local',
                'location'           => 'Junior School Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'code'               => 'DEPT-JR-ADM',
                'name'               => 'Junior School - Administration',
                'description'        => 'Administration department for Junior School.',
                'head_id'            => null,
                'annual_budget'      => 100000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 20000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'jr.admin@easyschool.local',
                'location'           => 'Junior School Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // Middle School
            [
                'code'               => 'DEPT-MD-ACA',
                'name'               => 'Middle School - Academy',
                'description'        => 'Academic department for Middle School.',
                'head_id'            => null,
                'annual_budget'      => 150000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 25000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'md.academy@easyschool.local',
                'location'           => 'Middle School Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'code'               => 'DEPT-MD-ADM',
                'name'               => 'Middle School - Administration',
                'description'        => 'Administration department for Middle School.',
                'head_id'            => null,
                'annual_budget'      => 100000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 20000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'md.admin@easyschool.local',
                'location'           => 'Middle School Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // Annex Building
            [
                'code'               => 'DEPT-AX-ACA',
                'name'               => 'Annex Building - Academy',
                'description'        => 'Academic department for Annex Building.',
                'head_id'            => null,
                'annual_budget'      => 120000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 20000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'ax.academy@easyschool.local',
                'location'           => 'Annex Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'code'               => 'DEPT-AX-ADM',
                'name'               => 'Annex Building - Administration',
                'description'        => 'Administration department for Annex Building.',
                'head_id'            => null,
                'annual_budget'      => 80000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 15000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'ax.admin@easyschool.local',
                'location'           => 'Annex Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],

            // Senior School
            [
                'code'               => 'DEPT-SR-ACA',
                'name'               => 'Senior School - Academy',
                'description'        => 'Academic department for Senior School.',
                'head_id'            => null,
                'annual_budget'      => 200000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 35000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'sr.academy@easyschool.local',
                'location'           => 'Senior School Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'code'               => 'DEPT-SR-ADM',
                'name'               => 'Senior School - Administration',
                'description'        => 'Administration department for Senior School.',
                'head_id'            => null,
                'annual_budget'      => 120000.00,
                'spent_amount'       => 0.00,
                'approval_threshold' => 20000.00,
                'is_active'          => true,
                'phone'              => null,
                'email'              => 'sr.admin@easyschool.local',
                'location'           => 'Senior School Building',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ];

        DB::table('departments')->insert($departments);
        $this->command->info('✓ Departments seeded: ' . count($departments));
    }
}