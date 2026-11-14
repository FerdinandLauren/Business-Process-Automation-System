<?php

namespace Tests\Feature;

use App\Models\ApprovalTask;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkflowTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_an_employee_sees_figures_for_their_own_requests_only(): void
    {
        $user = $this->user('ferdinand@arunika.test');

        $response = $this->actingAs($user)->get('/overview')->assertOk();

        $ownOpen = ServiceRequest::where('requester_id', $user->getKey())
            ->whereIn('status', ['submitted', 'under_review', 'revision_required'])
            ->count();

        $response->assertViewHas('openRequests', $ownOpen);
        $response->assertViewHas('revisionRequired', 1);
        $response->assertViewHas('draftCount', 0);
        $response->assertViewHas('isApprover', false);
        $response->assertViewHas('viewingAll', false);
    }

    public function test_an_approver_sees_only_tasks_assigned_to_them(): void
    {
        $andi = $this->user('andi@arunika.test');

        $response = $this->actingAs($andi)->get('/overview')->assertOk();

        $pending = ApprovalTask::where('assigned_approver_id', $andi->getKey())
            ->where('status', 'pending')
            ->count();

        $overdue = ApprovalTask::where('assigned_approver_id', $andi->getKey())
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $response->assertViewHas('pendingApprovals', fn ($tasks) => $tasks->count() === $pending);
        $response->assertViewHas('overdueCount', $overdue);
        $response->assertViewHas('isApprover', true);
    }

    public function test_an_administrator_sees_company_wide_figures(): void
    {
        $admin = $this->user('admin@arunika.test');

        $response = $this->actingAs($admin)->get('/overview')->assertOk();

        $response->assertViewHas('activeWorkflows', WorkflowTemplate::where('is_active', true)->count());
        $response->assertViewHas('viewingAll', true);
        $response->assertViewHas('recentActivity', fn ($logs) => $logs !== null && $logs->isNotEmpty());
    }

    public function test_the_status_distribution_reconciles_with_the_database(): void
    {
        $user = $this->user('admin@arunika.test');

        $response = $this->actingAs($user)->get('/overview')->assertOk();

        $expected = ServiceRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $response->assertViewHas('statusDistribution', function ($distribution) use ($expected) {
            return $distribution->sum() === (int) $expected->sum();
        });
    }
}