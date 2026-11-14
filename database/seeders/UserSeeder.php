<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'code');
        $roles = Role::pluck('id', 'name');

        $users = [
            ['name' => 'Ferdinand Lauren', 'email' => 'ferdinand@arunika.test', 'department' => 'HR', 'role' => 'employee', 'job_title' => 'People Operations Officer', 'employee_code' => 'ARU-1042'],
            ['name' => 'Andi Pratama', 'email' => 'andi@arunika.test', 'department' => 'HR', 'role' => 'department_manager', 'job_title' => 'Human Resources Manager', 'employee_code' => 'ARU-1007'],
            ['name' => 'Rina Kusuma', 'email' => 'rina@arunika.test', 'department' => 'IT', 'role' => 'department_manager', 'job_title' => 'IT Manager', 'employee_code' => 'ARU-1012'],
            ['name' => 'Budi Santoso', 'email' => 'budi@arunika.test', 'department' => 'FIN', 'role' => 'finance_manager', 'job_title' => 'Finance Manager', 'employee_code' => 'ARU-1003'],
            ['name' => 'Sari Wulandari', 'email' => 'sari@arunika.test', 'department' => 'FIN', 'role' => 'finance_admin', 'job_title' => 'Finance Administrator', 'employee_code' => 'ARU-1021'],
            ['name' => 'Dewi Anggraini', 'email' => 'dewi@arunika.test', 'department' => 'OPS', 'role' => 'process_admin', 'job_title' => 'Process Administrator', 'employee_code' => 'ARU-1009'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra@arunika.test', 'department' => 'MGT', 'role' => 'management', 'job_title' => 'Operations Director', 'employee_code' => 'ARU-1001'],
            ['name' => 'Aditya Nugroho', 'email' => 'admin@arunika.test', 'department' => 'IT', 'role' => 'system_admin', 'job_title' => 'System Administrator', 'employee_code' => 'ARU-1015'],
            ['name' => 'Nadia Prameswari', 'email' => 'nadia@arunika.test', 'department' => 'FIN', 'role' => 'employee', 'job_title' => 'Financial Analyst', 'employee_code' => 'ARU-1036'],
            ['name' => 'Yusuf Hidayat', 'email' => 'yusuf@arunika.test', 'department' => 'PRC', 'role' => 'employee', 'job_title' => 'Procurement Officer', 'employee_code' => 'ARU-1029'],
            ['name' => 'Maya Sutedja', 'email' => 'maya@arunika.test', 'department' => 'GA', 'role' => 'employee', 'job_title' => 'General Affairs Officer', 'employee_code' => 'ARU-1033'],
        ];

        foreach ($users as $definition) {
            $user = User::updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => Hash::make('password'),
                    'department_id' => $departments[$definition['department']] ?? null,
                    'employee_code' => $definition['employee_code'],
                    'job_title' => $definition['job_title'],
                    'is_active' => true,
                ],
            );

            $user->roles()->sync([$roles[$definition['role']]]);
        }
    }
}
