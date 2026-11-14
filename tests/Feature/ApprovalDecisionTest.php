<?php

namespace Tests\Feature;

use App\Enums\InstanceStatus;
use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use App\Exceptions\WorkflowException;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApprovalDecisionTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalService $approvals;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('bpa.attachments.disk'));

        $this->seed();

        $this->approvals = app(ApprovalService::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function pendingTask(string $requestNumber)
    {
        return ServiceRequest::where('request_number', $requestNumber)
            ->firstOrFail()
            ->workflowInstance
            ->tasks()
            ->where('status', 'pending')
            ->firstOrFail();
    }

    public function test_an_approval_advances_the_request_to_the_next_stage(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');
        $approver = $task->approver;

        $decided = $this->approvals->approve($task, $approver, 'Access is required for the reconciliation.');

        $this->assertSame(TaskStatus::Approved, $decided->status);
        $this->assertNotNull($decided->completed_at);
        $this->assertDatabaseHas('approval_histories', [
            'approval_task_id' => $task->getKey(),
            'actor_id' => $approver->getKey(),
            'action' => 'approved',
        ]);

        $request = $task->workflowInstance->request->refresh();
        $this->assertSame(RequestStatus::UnderReview, $request->status);
        $this->assertSame(3, $request->workflowInstance->refresh()->current_stage_order);

        $next = $request->workflowInstance->tasks()->where('status', 'pending')->sole();
        $this->assertSame('IT Provisioning', $next->stage->stage_name);
        $this->assertSame('admin@arunika.test', $next->approver->email);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $request->requester_id,
            'type' => 'request.approved_stage',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'approval.approved']);
    }

    public function test_the_final_approval_completes_the_request(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');

        $this->approvals->approve($task, $task->approver, 'Stage two approved.');

        $final = $this->pendingTask('REQ-202611-0005');
        $this->approvals->approve($final, $final->approver, 'Provisioned.');

        $request = ServiceRequest::where('request_number', 'REQ-202611-0005')->firstOrFail();

        $this->assertSame(RequestStatus::Approved, $request->status);
        $this->assertNotNull($request->completed_at);
        $this->assertSame(InstanceStatus::Completed, $request->workflowInstance->status);
        $this->assertSame(0, $request->workflowInstance->tasks()->where('status', 'pending')->count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $request->requester_id,
            'type' => 'request.completed',
        ]);
    }

    public function test_only_the_assigned_approver_may_decide(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');
        $otherManager = $this->user('andi@arunika.test');

        $this->assertTrue(Gate::forUser($otherManager)->denies('decide', $task));

        $this->expectException(AuthorizationException::class);
        $this->approvals->approve($task, $otherManager, 'I should not be able to do this.');
    }

    public function test_a_task_cannot_be_decided_twice(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');
        $approver = $task->approver;

        $this->approvals->approve($task, $approver, 'First decision.');

        $this->expectException(WorkflowException::class);
        $this->approvals->approve($task->refresh(), $approver, 'Second decision.');
    }

    public function test_a_rejection_requires_a_written_reason(): void
    {
        $task = $this->pendingTask('REQ-202611-0004');

        try {
            $this->approvals->reject($task, $task->approver, 'No');
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('comment', $exception->errors());
        }

        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
    }

    public function test_a_rejection_stops_the_workflow_and_notifies_the_requester(): void
    {
        $task = $this->pendingTask('REQ-202611-0004');
        $approver = $task->approver;

        $this->approvals->reject($task, $approver, 'The team is short-staffed during that week.');

        $request = $task->workflowInstance->request->refresh();

        $this->assertSame(RequestStatus::Rejected, $request->status);
        $this->assertNotNull($request->completed_at);
        $this->assertSame(InstanceStatus::Rejected, $request->workflowInstance->status);
        $this->assertSame(0, $request->workflowInstance->tasks()->where('status', 'pending')->count());

        $this->assertDatabaseHas('notifications', [
            'user_id' => $request->requester_id,
            'type' => 'request.rejected',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'approval.rejected']);
    }

    public function test_a_revision_request_returns_the_request_to_the_requester(): void
    {
        $task = $this->pendingTask('REQ-202611-0004');
        $approver = $task->approver;

        $this->approvals->requestRevision($task, $approver, 'Please confirm who covers the finance reconciliation.');

        $request = $task->workflowInstance->request->refresh();

        $this->assertSame(RequestStatus::RevisionRequired, $request->status);
        $this->assertSame(InstanceStatus::Running, $request->workflowInstance->status);
        $this->assertSame(TaskStatus::RevisionRequested, $task->refresh()->status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $request->requester_id,
            'type' => 'request.revision_required',
        ]);
    }

    public function test_an_inactive_approver_cannot_decide(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');
        $approver = $task->approver;
        $approver->forceFill(['is_active' => false])->save();

        $this->assertTrue(Gate::forUser($approver->refresh())->denies('decide', $task));
    }
}
