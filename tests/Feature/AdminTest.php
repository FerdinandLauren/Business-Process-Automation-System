<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_the_last_system_administrator_cannot_be_demoted(): void
    {
        $admin = $this->user('admin@arunika.test');
        $employeeRole = Role::where('name', 'employee')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'department_id' => $admin->department_id,
                'employee_code' => $admin->employee_code,
                'job_title' => $admin->job_title,
                'is_active' => 1,
                'roles' => [$employeeRole->getKey()],
            ])
            ->assertStatus(422);

        $this->assertTrue($admin->refresh()->hasRole('system_admin'));
    }

    public function test_an_administrator_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->user('admin@arunika.test');

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin))
            ->assertSessionHasErrors('status');

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_the_permission_matrix_never_touches_the_system_administrator_column(): void
    {
        $admin = $this->user('admin@arunika.test');
        $systemAdmin = Role::where('name', 'system_admin')->firstOrFail();
        $before = $systemAdmin->permissions()->pluck('name')->sort()->values()->all();

        $this->actingAs($admin)
            ->patch(route('admin.roles.update'), [
                'matrix' => [
                    $systemAdmin->getKey() => [],
                ],
            ])
            ->assertRedirect();

        $after = $systemAdmin->refresh()->permissions()->pluck('name')->sort()->values()->all();

        $this->assertSame($before, $after);
        $this->assertContains('user.manage', $after);
    }

    public function test_changing_a_role_writes_an_audit_entry(): void
    {
        $admin = $this->user('admin@arunika.test');
        $manager = Role::where('name', 'department_manager')->firstOrFail();

        $remaining = $manager->permissions
            ->reject(fn (Permission $permission) => $permission->name === 'approval.process')
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->patch(route('admin.roles.update'), [
                'matrix' => [
                    $manager->getKey() => $remaining,
                ],
            ])
            ->assertRedirect();

        $this->assertFalse($manager->refresh()->permissions()->where('name', 'approval.process')->exists());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role.permissions_updated',
            'entity_type' => 'Role',
            'entity_id' => $manager->getKey(),
        ]);
    }

    public function test_an_administrator_can_create_a_user(): void
    {
        $admin = $this->user('admin@arunika.test');
        $employeeRole = Role::where('name', 'employee')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Ayu Lestari',
                'email' => 'ayu@arunika.test',
                'password' => 'temporary-pass',
                'password_confirmation' => 'temporary-pass',
                'department_id' => $admin->department_id,
                'employee_code' => 'ARU-9001',
                'job_title' => 'Analyst',
                'is_active' => 1,
                'roles' => [$employeeRole->getKey()],
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'ayu@arunika.test')->firstOrFail();

        $this->assertTrue($created->hasRole('employee'));
        $this->assertNotSame('temporary-pass', $created->password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'entity_type' => 'User',
            'entity_id' => $created->getKey(),
        ]);
    }

    public function test_the_audit_trail_can_be_filtered_by_action(): void
    {
        $admin = $this->user('admin@arunika.test');

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['action' => 'request.submitted']))
            ->assertOk()
            ->assertSee('request.submitted');
    }
}
