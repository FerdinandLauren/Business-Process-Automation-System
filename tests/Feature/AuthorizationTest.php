<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
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

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        foreach (['/overview', '/requests', '/approvals', '/notifications', '/reports', '/admin'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_an_employee_cannot_open_administration_or_reports(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        $this->get('/admin')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->get('/reports/export?report=requests')->assertForbidden();
    }

    public function test_a_department_manager_can_approve_but_not_administer(): void
    {
        $this->actingAs($this->user('andi@arunika.test'));

        $this->get('/approvals')->assertOk();
        $this->get('/admin')->assertForbidden();
        $this->get('/reports')->assertForbidden();
    }

    public function test_a_finance_manager_can_view_reports(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $this->get('/reports')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_management_can_view_reports_but_not_administration(): void
    {
        $this->actingAs($this->user('hendra@arunika.test'));

        $this->get('/reports')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_a_system_administrator_can_reach_every_admin_section(): void
    {
        $this->actingAs($this->user('admin@arunika.test'));

        foreach ([
            '/admin',
            '/admin/users',
            '/admin/roles',
            '/admin/departments',
            '/admin/request-types',
            '/admin/audit',
            '/reports',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_a_process_administrator_can_manage_workflows_but_not_users(): void
    {
        $this->actingAs($this->user('dewi@arunika.test'));

        $this->get('/workflows')->assertOk();
        $this->get('/admin/request-types')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
    }

    public function test_an_employee_cannot_read_another_persons_request(): void
    {
        $owner = $this->user('ferdinand@arunika.test');
        $other = $this->user('maya@arunika.test');

        $request = ServiceRequest::where('requester_id', $owner->getKey())->firstOrFail();

        $this->actingAs($other)->get(route('requests.show', $request))->assertForbidden();
    }

    public function test_an_assigned_approver_can_read_the_request_they_are_deciding(): void
    {
        $request = ServiceRequest::where('request_number', 'REQ-202611-0005')->firstOrFail();
        $task = $request->workflowInstance->tasks()->where('status', 'pending')->firstOrFail();

        $this->actingAs($task->approver)
            ->get(route('requests.show', $request))
            ->assertOk()
            ->assertSee($request->request_number);
    }

    public function test_only_the_requester_can_edit_a_draft(): void
    {
        $owner = $this->user('ferdinand@arunika.test');
        $type = \App\Models\RequestType::where('code', 'LEAVE')->firstOrFail();

        $request = app(\App\Services\RequestService::class)->save($owner, $type, [
            'title' => 'Draft leave request for the year-end break',
            'form_data' => [],
        ]);

        $this->actingAs($owner)
            ->get(route('requests.edit', $request))
            ->assertOk();

        $this->actingAs($this->user('maya@arunika.test'))
            ->get(route('requests.edit', $request))
            ->assertForbidden();
    }
}
