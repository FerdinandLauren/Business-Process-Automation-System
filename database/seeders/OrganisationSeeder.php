<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class OrganisationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'People operations, leave administration and employee services.'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Reimbursements, payments and financial controls.'],
            ['name' => 'General Affairs', 'code' => 'GA', 'description' => 'Office facilities, travel and general administration.'],
            ['name' => 'Procurement', 'code' => 'PRC', 'description' => 'Purchasing and supplier coordination.'],
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Systems, access management and infrastructure.'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Day-to-day industrial operations and process governance.'],
            ['name' => 'Management', 'code' => 'MGT', 'description' => 'Company performance and operational oversight.'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
