<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Department;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * APPROVAL CHAIN
 * ──────────────────────────────────────────────────
 * Level 1 → department.head_id  (strict, no role)
 * Level 2 → po_officer
 * Level 3 → po_admin_officer
 * Level 4 → po_director_admin
 * Level 5 → managing_director OR deputy_managing_director
 * ──────────────────────────────────────────────────
 * Any role can CREATE a PR.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant   = Tenant::where('subdomain', 'presidency')->firstOrFail();
        $depts    = Department::pluck('id', 'code');
        $branches = Branch::pluck('id', 'code');

        // Super Admin
        $this->make(['tenant_id' => null, 'name' => 'Murad',
                     'email' => 'murad@murad.bd', 'role' => 'director_admin']);

        // School Leadership
        foreach ([
            ['Dr. Imam Hasan Reza', 'rector@easyschool.local',   'rector',                   null,          null],
            ['Jasim Uddin',         'director@easyschool.local',  'director_admin',            null,          null],
            ['Gulzar Alam Alamgir', 'md@easyschool.local',        'managing_director',         null,          null],
            ['Masudul Amin Khan',   'dmd@easyschool.local',       'deputy_managing_director',  null,          null],
            ['E.U.M Intekhab',      'vp.senior@easyschool.local', 'vice_principal',            'DEPT-SR-ADM', 'BR-SR'],
            ['Jasim Uddin VP',      'vp.middle@easyschool.local', 'vice_principal',            'DEPT-MD-ADM', 'BR-MD'],
            ['Firoz Ahmed',         'vp.junior@easyschool.local', 'vice_principal',            'DEPT-JR-ADM', 'BR-JR'],
            ['Junior Principal',    'junior@murad.bd',            'principal',                 'DEPT-JR-ADM', 'BR-JR'],
            ['Middle Principal',    'middle@murad.bd',            'principal',                 'DEPT-MD-ADM', 'BR-MD'],
            ['Annex Principal',     'annex@murad.bd',             'principal',                 'DEPT-AX-ADM', 'BR-AX'],
            ['Senior Principal',    'senior@murad.bd',            'principal',                 'DEPT-SR-ADM', 'BR-SR'],
            ['Highcare Coordinator','highcare@murad.bd',          'chief_coordinator',         'DEPT-SR-ADM', 'BR-SR'],
        ] as [$name, $email, $role, $dept, $branch]) {
            $this->make([
                'tenant_id'     => $tenant->id,
                'name'          => $name,
                'email'         => $email,
                'role'          => $role,
                'department_id' => $dept   ? ($depts[$dept]   ?? null) : null,
                'branch_id'     => $branch ? ($branches[$branch] ?? null) : null,
            ]);
        }

        // PO Approval Chain — Level 2,3,4,5 only (Level 1 = dept head)
        foreach ([
            ['PO Officer',        'po.officer@easyschool.local',       'po_officer',              'Level 2'],
            ['PO Admin Officer',  'po.admin.officer@easyschool.local', 'po_admin_officer',        'Level 3'],
            ['PO Director Admin', 'po.director@easyschool.local',      'po_director_admin',       'Level 4'],
            ['Gulzar (MD)',       'po.md@easyschool.local',            'managing_director',       'Level 5'],
            ['Masudul (DMD)',     'po.dmd@easyschool.local',           'deputy_managing_director','Level 5'],
        ] as [$name, $email, $role, $level]) {
            $this->make([
                'tenant_id' => $tenant->id,
                'name'      => $name,
                'email'     => $email,
                'role'      => $role,
            ], "APPROVER {$level}");
        }

        // Test requesters
        foreach ([
            ['Test Teacher', 'teacher@easyschool.local', 'teacher', 'DEPT-JR-ADM', 'BR-JR'],
            ['Test Staff',   'staff@easyschool.local',   'staff',   'DEPT-MD-ADM', 'BR-MD'],
        ] as [$name, $email, $role, $dept, $branch]) {
            $this->make([
                'tenant_id'     => $tenant->id,
                'name'          => $name,
                'email'         => $email,
                'role'          => $role,
                'department_id' => $depts[$dept]      ?? null,
                'branch_id'     => $branches[$branch] ?? null,
            ], 'REQUESTER');
        }

        $this->command->info('');
        $this->command->info('Password: password');
        $this->command->info('Level 1 → whoever is set as dept head in Settings → Departments');
        $this->command->info('Level 2 → po.officer@easyschool.local');
        $this->command->info('Level 3 → po.admin.officer@easyschool.local');
        $this->command->info('Level 4 → po.director@easyschool.local');
        $this->command->info('Level 5 → po.md@ OR po.dmd@easyschool.local');
    }

    private function make(array $data, string $tag = ''): void
    {
        $data = array_merge(['password' => Hash::make('password'), 'is_active' => true,
                             'department_id' => null, 'branch_id' => null], $data);
        User::firstOrCreate(['email' => $data['email']], $data);
        $this->command->info("✓ {$data['name']} ({$data['role']})" . ($tag ? " [{$tag}]" : ''));
    }
}