<?php

namespace Database\Seeders;

use App\Models\ApprovalPolicy;
use Illuminate\Database\Seeder;

class ApprovalPolicySeeder extends Seeder
{
    public function run(): void
    {
        ApprovalPolicy::whereNull('tenant_id')->delete();

        // Level 1 role_name ['dept_head'] is a MARKER — ApprovalService
        // ignores it and uses department.head_id directly (strict).
        $chain = [
            [1, 'Department Head',         ['dept_head']],
            [2, 'PO Officer Review',        ['po_officer']],
            [3, 'Admin Officer Validation', ['po_admin_officer']],
            [4, 'Director Admin Approval',  ['po_director_admin']],
            [5, 'MD / DMD Final Approval',  ['managing_director', 'deputy_managing_director']],
        ];

        foreach ($chain as [$level, $name, $roles]) {
            ApprovalPolicy::create([
                'tenant_id'  => null, 'name' => $name,
                'level'      => $level, 'min_amount' => 0, 'max_amount' => null,
                'role_name'  => $roles, 'is_active' => true, 'sort_order' => $level,
            ]);
        }

        $this->command->table(['Level', 'Step', 'Resolved By'], [
            [1, 'Department Head',         'departments.head_id (strict)'],
            [2, 'PO Officer Review',        'role: po_officer'],
            [3, 'Admin Officer Validation', 'role: po_admin_officer'],
            [4, 'Director Admin Approval',  'role: po_director_admin'],
            [5, 'MD / DMD Final Approval',  'role: managing_director OR deputy_managing_director'],
        ]);
    }
}