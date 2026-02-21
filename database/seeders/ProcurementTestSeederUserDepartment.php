<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Department;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProcurementTestSeederUserDepartment extends Seeder
{
    private array $depts = [
        ['name' => 'IT Department',      'prefix' => 'it'],
        ['name' => 'HR Department',      'prefix' => 'hr'],
        ['name' => 'Finance Department', 'prefix' => 'fin'],
        ['name' => 'Library',            'prefix' => 'lib'],
        ['name' => 'Science Department', 'prefix' => 'sci'],
    ];

    public function run(): void
    {
        $tenant = Tenant::where('subdomain', 'presidency')->firstOrFail();
        $branch = Branch::first(); // branches has no tenant_id column

        foreach ($this->depts as $d) {
            $headEmail  = "{$d['prefix']}.head@easyschool.local";
            $staffEmail = "{$d['prefix']}.staff@easyschool.local";
            $code       = 'DEPT-' . strtoupper($d['prefix']);

            // 1. Head user
            $head = User::where('email', $headEmail)->first();
            if (!$head) {
                $head = new User();
                $head->name      = $d['name'] . ' Head';
                $head->email     = $headEmail;
                $head->password  = Hash::make('password');
                $head->tenant_id = $tenant->id;
                $head->role      = 'dept_head';
                $head->is_active = true;
                $head->branch_id = $branch?->id;
                $head->save();
            }

            // 2. Department
            $dept = Department::where('code', $code)->first();
            if (!$dept) {
                $dept = new Department();
                $dept->code      = $code;
                $dept->name      = $d['name'];
                $dept->is_active = true;
            }
            $dept->head_id = $head->id;
            $dept->save();

            // 3. Link head → dept
            $head->department_id = $dept->id;
            $head->save();

            // 4. Staff user
            if (!User::where('email', $staffEmail)->exists()) {
                $staff = new User();
                $staff->name          = $d['name'] . ' Staff';
                $staff->email         = $staffEmail;
                $staff->password      = Hash::make('password');
                $staff->tenant_id     = $tenant->id;
                $staff->role          = 'staff';
                $staff->is_active     = true;
                $staff->department_id = $dept->id;
                $staff->branch_id     = $branch?->id;
                $staff->save();
            }

            $this->command->info("✓ {$d['name']} [head_id={$head->id}, dept_id={$dept->id}]");
        }

        $this->command->table(
            ['Staff Login', 'Dept Head Login'],
            collect($this->depts)->map(fn($d) => [
                "{$d['prefix']}.staff@easyschool.local",
                "{$d['prefix']}.head@easyschool.local",
            ])->toArray()
        );
        $this->command->info('Password: password');
    }
}