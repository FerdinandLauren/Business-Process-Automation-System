<?php

namespace Tests\Feature\Livewire;

use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use App\Livewire\ApprovalDetail;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalDetailComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('bpa.attachments.disk'));

        $this->seed();
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

    public function test_the_assigned_approver_can_approve_through_the_component(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');

        $this->actingAs($task->approver);

        Livewire::test(ApprovalDetail::class, ['task' => $task])
            ->call('openDecision', 'approve')
            ->assertSet('pendingAction', 'approve')
            ->set('comment', 'Required for the monthly reconciliation.')
            ->call('confirmDecision')
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(TaskStatus::Approved, $task->refresh()->status);
        $this->assertSame(3, $task->workflowInstance->refresh()->current_stage_order);
    }

    public function test_a_rejection_needs_a_comment_before_it_is_accepted(): void
    {
        $task = $this->pendingTask('REQ-202611-0004');

        $this->actingAs($task->approver);

        Livewire::test(ApprovalDetail::class, ['task' => $task])
            ->call('openDecision', 'reject')
            ->set('comment', 'No')
            ->call('confirmDecision')
            ->assertHasErrors('comment');

        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
    }

    public function test_a_rejection_through_the_component_closes_the_request(): void
    {
        $task = $this->pendingTask('REQ-202611-0004');

        $this->actingAs($task->approver);

        Livewire::test(ApprovalDetail::class, ['task' => $task])
            ->call('openDecision', 'reject')
            ->set('comment', 'The team is short-staffed that week.')
            ->call('confirmDecision')
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(RequestStatus::Rejected, $task->workflowInstance->request->refresh()->status);
    }

    public function test_a_user_who_is_not_the_approver_cannot_open_the_decision_screen(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');

        $this->actingAs($this->user('andi@arunika.test'))
            ->get(route('approvals.show', $task))
            ->assertForbidden();
    }

    public function test_the_component_records_the_decision_history(): void
    {
        $task = $this->pendingTask('REQ-202611-0005');
        $approver = $task->approver;

        $this->actingAs($approver);

        Livewire::test(ApprovalDetail::class, ['task' => $task])
            ->call('openDecision', 'approve')
            ->set('comment', 'Access approved for the reconciliation.')
            ->call('confirmDecision');

        $this->assertDatabaseHas('approval_histories', [
            'approval_task_id' => $task->getKey(),
            'actor_id' => $approver->getKey(),
            'action' => 'approved',
            'comment' => 'Access approved for the reconciliation.',
        ]);
    }
}