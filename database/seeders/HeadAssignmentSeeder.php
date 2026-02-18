<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeadAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $assignments = [
            // Branch heads
            ['branches',    'code', 'BR-JR', 'vp.junior@easyschool.local'],
            ['branches',    'code', 'BR-MD', 'vp.middle@easyschool.local'],
            ['branches',    'code', 'BR-AX', 'vp.middle@easyschool.local'],
            ['branches',    'code', 'BR-SR', 'vp.senior@easyschool.local'],

            // Department heads
            ['departments', 'code', 'DEPT-JR-ADM', 'vp.junior@easyschool.local'],
            ['departments', 'code', 'DEPT-JR-ACA', 'vp.junior@easyschool.local'],
            ['departments', 'code', 'DEPT-MD-ADM', 'vp.middle@easyschool.local'],
            ['departments', 'code', 'DEPT-MD-ACA', 'vp.middle@easyschool.local'],
            ['departments', 'code', 'DEPT-AX-ADM', 'vp.middle@easyschool.local'],
            ['departments', 'code', 'DEPT-AX-ACA', 'vp.middle@easyschool.local'],
            ['departments', 'code', 'DEPT-SR-ADM', 'vp.senior@easyschool.local'],
            ['departments', 'code', 'DEPT-SR-ACA', 'vp.senior@easyschool.local'],
        ];

        foreach ($assignments as [$table, $col, $val, $email]) {
            $userId = DB::table('users')->where('email', $email)->value('id');

            if ($userId) {
                DB::table($table)->where($col, $val)->update(['head_id' => $userId]);
                $this->command->info("✓ {$email} → {$table} [{$val}]");
            } else {
                $this->command->warn("⚠ User not found: {$email}");
            }
        }
    }
}