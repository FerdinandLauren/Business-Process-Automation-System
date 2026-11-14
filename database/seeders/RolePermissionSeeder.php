<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'request.create', 'label' => 'Create requests', 'description' => 'Create and submit administrative requests.'],
            ['name' => 'request.view.any', 'label' => 'View all requests', 'description' => 'Read requests across departments.'],
            ['name' => 'approval.process', 'label' => 'Process approvals', 'description' => 'Decide assigned approval tasks.'],
            ['name' => 'workflow.manage', 'label' => 'Manage workflows', 'description' => 'Configure request types, forms and approval workflows.'],
            ['name' => 'user.manage', 'label' => 'Manage users', 'description' => 'Create and maintain user accounts and roles.'],
            ['name' => 'organisation.manage', 'label' => 'Manage organisation', 'description' => 'Maintain departments and organisational structure.'],
            ['name' => 'report.view', 'label' => 'View reports', 'description' => 'Access operational reporting and exports.'],
            ['name' => 'audit.view', 'label' => 'View audit trail', 'description' => 'Read the append-only audit log.'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }

        $roles = [
            'employee' => [
                'label' => 'Employee',
                'description' => 'Submits administrative requests and tracks their progress.',
                'permissions' => ['request.create'],
            ],
            'department_manager' => [
                'label' => 'Department Manager',
                'description' => 'Reviews requests raised by their department.',
                'permissions' => ['request.create', 'approval.process'],
            ],
            'finance_manager' => [
                'label' => 'Finance Manager',
                'description' => 'Approves financial commitments and reimbursements.',
                'permissions' => ['request.create', 'approval.process', 'report.view'],
            ],
            'finance_admin' => [
                'label' => 'Finance Administrator',
                'description' => 'Completes the financial processing stage of a request.',
                'permissions' => ['request.create', 'approval.process', 'report.view'],
            ],
            'process_admin' => [
                'label' => 'Process Administrator',
                'description' => 'Owns request types, forms and approval workflows.',
                'permissions' => ['workflow.manage', 'report.view', 'request.view.any'],
            ],
            'system_admin' => [
                'label' => 'System Administrator',
                'description' => 'Manages accounts, organisation data and system access.',
                'permissions' => ['user.manage', 'organisation.manage', 'workflow.manage', 'audit.view', 'report.view', 'request.view.any'],
            ],
            'management' => [
                'label' => 'Management',
                'description' => 'Monitors process performance across the company.',
                'permissions' => ['report.view', 'request.view.any'],
            ],
        ];

        foreach ($roles as $name => $definition) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'is_system' => true,
                ],
            );

            $role->permissions()->sync(
                Permission::whereIn('name', $definition['permissions'])->pluck('id')->all()
            );
        }
    }
}
