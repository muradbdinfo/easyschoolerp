<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $branches = [
            [
                'code'             => 'BR-JR',
                'name'             => 'Junior School',
                'description'      => 'Junior campus of Presidency International School.',
                'head_id'          => null,
                'address'          => 'Presidency Campus, Junior Block, Chattogram',
                'city'             => 'Chattogram',
                'district'         => 'Chattogram',
                'postal_code'      => '4100',
                'country'          => 'Bangladesh',
                'phone'            => '+880-31-100001',
                'email'            => 'junior@easyschool.local',
                'fax'              => null,
                'latitude'         => 22.3275000,
                'longitude'        => 91.8150000,
                'established_date' => '2005-01-01',
                'is_active'        => true,
                'is_main_branch'   => false,
                'student_capacity' => 600,
                'staff_count'      => 40,
                'annual_budget'    => 1500000.00,
                'settings'         => json_encode(['timezone' => 'Asia/Dhaka', 'currency' => 'BDT']),
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'code'             => 'BR-MD',
                'name'             => 'Middle School',
                'description'      => 'Middle campus of Presidency International School.',
                'head_id'          => null,
                'address'          => 'Presidency Campus, Middle Block, Chattogram',
                'city'             => 'Chattogram',
                'district'         => 'Chattogram',
                'postal_code'      => '4100',
                'country'          => 'Bangladesh',
                'phone'            => '+880-31-100002',
                'email'            => 'middle@easyschool.local',
                'fax'              => null,
                'latitude'         => 22.3280000,
                'longitude'        => 91.8155000,
                'established_date' => '2007-01-01',
                'is_active'        => true,
                'is_main_branch'   => false,
                'student_capacity' => 700,
                'staff_count'      => 50,
                'annual_budget'    => 1800000.00,
                'settings'         => json_encode(['timezone' => 'Asia/Dhaka', 'currency' => 'BDT']),
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'code'             => 'BR-AX',
                'name'             => 'Annex Building',
                'description'      => 'Annex campus of Presidency International School.',
                'head_id'          => null,
                'address'          => 'Presidency Campus, Annex Block, Chattogram',
                'city'             => 'Chattogram',
                'district'         => 'Chattogram',
                'postal_code'      => '4100',
                'country'          => 'Bangladesh',
                'phone'            => '+880-31-100003',
                'email'            => 'annex@easyschool.local',
                'fax'              => null,
                'latitude'         => 22.3285000,
                'longitude'        => 91.8160000,
                'established_date' => '2010-01-01',
                'is_active'        => true,
                'is_main_branch'   => false,
                'student_capacity' => 400,
                'staff_count'      => 30,
                'annual_budget'    => 1000000.00,
                'settings'         => json_encode(['timezone' => 'Asia/Dhaka', 'currency' => 'BDT']),
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'code'             => 'BR-SR',
                'name'             => 'Senior School',
                'description'      => 'Senior campus of Presidency International School.',
                'head_id'          => null,
                'address'          => 'Presidency Campus, Senior Block, Chattogram',
                'city'             => 'Chattogram',
                'district'         => 'Chattogram',
                'postal_code'      => '4100',
                'country'          => 'Bangladesh',
                'phone'            => '+880-31-100004',
                'email'            => 'senior@easyschool.local',
                'fax'              => null,
                'latitude'         => 22.3290000,
                'longitude'        => 91.8165000,
                'established_date' => '2003-01-01',
                'is_active'        => true,
                'is_main_branch'   => true,
                'student_capacity' => 1000,
                'staff_count'      => 80,
                'annual_budget'    => 3000000.00,
                'settings'         => json_encode(['timezone' => 'Asia/Dhaka', 'currency' => 'BDT']),
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];

        DB::table('branches')->insert($branches);
        $this->command->info('✓ Branches seeded: ' . count($branches));
    }
}