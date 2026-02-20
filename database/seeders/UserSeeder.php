<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Department;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant   = Tenant::where('subdomain', 'presidency')->firstOrFail();
        $depts    = Department::pluck('id', 'code');
        $branches = Branch::pluck('id', 'code');

        // ── Super Admin — no tenant ───────────────────────────────────────
        $this->createUser([
            'tenant_id'     => null,
            'name'          => 'Murad',
            'email'         => 'murad@murad.bd',
            'password'      => Hash::make('password'),
            'role'          => 'director_admin',
            'department_id' => null,
            'branch_id'     => null,
            'is_active'     => true,
        ], 'SUPER ADMIN');

        // ── Tenant Users ──────────────────────────────────────────────────
        $tenantUsers = [
            [
                'name'          => 'Dr. Imam Hasan Reza',
                'email'         => 'rector@easyschool.local',
                'role'          => 'rector',
                'department_id' => null,
                'branch_id'     => null,
            ],
            [
                'name'          => 'Jasim Uddin',
                'email'         => 'director@easyschool.local',
                'role'          => 'director_admin',
                  'department_id' => null,
                'branch_id'     => null,
            ],
            [
                'name'          => 'Gulzar Alam Alamgir',
                'email'         => 'md@easyschool.local',
                'role'          => 'managing_director',
                 'department_id' => null,
                'branch_id'     => null,
            ],
            [
                'name'          => 'Masudul Amin Khan',
                'email'         => 'dmd@easyschool.local',
                'role'          => 'deputy_managing_director',
                  'department_id' => null,
                'branch_id'     => null,
            ],

            // Vice Principals
            [
                'name'          => 'E.U.M Intekhab',
                'email'         => 'vp.senior@easyschool.local',
                'role'          => 'vice_principal',
                'department_id' => $depts['DEPT-SR-ADM'] ?? null,
                'branch_id'     => $branches['BR-SR'] ?? null,
            ],
            [
                'name'          => 'Jasim Uddin',
                'email'         => 'vp.middle@easyschool.local',
                'role'          => 'vice_principal',
                'department_id' => $depts['DEPT-MD-ADM'] ?? null,
                'branch_id'     => $branches['BR-MD'] ?? null,
            ],
            [
                'name'          => 'Firoz Ahmed',
                'email'         => 'vp.junior@easyschool.local',
                'role'          => 'vice_principal',
                'department_id' => $depts['DEPT-JR-ADM'] ?? null,
                'branch_id'     => $branches['BR-JR'] ?? null,
            ],

            // Branch test accounts
            [
                'name'          => 'Junior School Principal',
                'email'         => 'junior@murad.bd',
                'role'          => 'principal',
                'department_id' => $depts['DEPT-JR-ADM'] ?? null,
                'branch_id'     => $branches['BR-JR'] ?? null,
            ],
            [
                'name'          => 'Middle School Principal',
                'email'         => 'middle@murad.bd',
                'role'          => 'principal',
                'department_id' => $depts['DEPT-MD-ADM'] ?? null,
                'branch_id'     => $branches['BR-MD'] ?? null,
            ],
            [
                'name'          => 'Annex Building Principal',
                'email'         => 'annex@murad.bd',
                'role'          => 'principal',
                'department_id' => $depts['DEPT-AX-ADM'] ?? null,
                'branch_id'     => $branches['BR-AX'] ?? null,
            ],
            [
                'name'          => 'Senior School Principal',
                'email'         => 'senior@murad.bd',
                'role'          => 'principal',
                'department_id' => $depts['DEPT-SR-ADM'] ?? null,
                'branch_id'     => $branches['BR-SR'] ?? null,
            ],
            [
                'name'          => 'Highcare Coordinator',
                'email'         => 'highcare@murad.bd',
                'role'          => 'chief_coordinator',
                'department_id' => $depts['DEPT-SR-ADM'] ?? null,
                'branch_id'     => $branches['BR-SR'] ?? null,
            ],
        ];

        foreach ($tenantUsers as $data) {
            $this->createUser(array_merge($data, [
                'tenant_id' => $tenant->id,
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]));
        }

        $this->command->info('');
        $this->command->info('All passwords: password');
    }

    private function createUser(array $data, string $tag = ''): void
    {
        User::firstOrCreate(['email' => $data['email']], $data);
        $label = $tag ? " [{$tag}]" : '';
        $this->command->info("✓ {$data['name']} <{$data['email']}> ({$data['role']}){$label}");
    }
}