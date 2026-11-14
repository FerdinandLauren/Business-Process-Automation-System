<?php

namespace Tests\Feature;

use App\Enums\InstanceStatus;
use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private RequestService $requests;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('bpa.attachments.disk'));

        $this->seed();

        $this->requests = app(RequestService::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function leavePayload(): array
    {
        return [
            'title' => 'Annual leave for the December break',
            'description' => 'Five working days, handover agreed with the team lead.',
            'priority' => 'normal',
            'form_data' => [
                'leave_type' => 'Annual leave',
                'start_date' => '2026-12-08',
                'end_date' => '2026-12-12',
                'days' => '5',
                'handover' => 'Maya Sutedja',
                'reason' => 'Family holiday.',
            ],
        ];
    }

    public function test_a_draft_can_be_saved_with_partial_data(): void
    {
        $request = $this->requests->save($this->user('ferdinand@arunika.test'), RequestType::where('code', 'LEAVE')->firstOrFail(), [
            'title' => 'Draft leave request for the year-end break',
            'form_data' => ['leave_type' => 'Annual leave'],
        ]);

        $this->assertSame(RequestStatus::Draft, $request->status);
        $this->assertSame('HR', $request->department->code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.draft_created']);
    }

    public function test_submitting_a_complete_request_starts_the_workflow(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        $request = $this->requests->save($requester, RequestType::where('code', 'LEAVE')->firstOrFail(), $this->leavePayload());
        $request = $this->requests->submit($request, $requester);

        $this->assertSame(RequestStatus::UnderReview, $request->status);
        $this->assertNotNull($request->submitted_at);

        $instance = $request->workflowInstance;
        $this->assertNotNull($instance);
        $this->assertSame(InstanceStatus::Running, $instance->status);
        $this->assertSame(1, $instance->current_stage_order);

        $task = $instance->tasks()->sole();
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertSame('andi@arunika.test', $task->approver->email);
        $this->assertNotNull($task->due_at);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $task->assigned_approver_id,
            'type' => 'task.assigned',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.submitted']);
    }

    public function test_submission_is_blocked_when_required_fields_are_missing(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        $request = $this->requests->save($requester, RequestType::where('code', 'LEAVE')->firstOrFail(), [
            'title' => 'Incomplete leave request',
            'form_data' => [],
        ]);

        try {
            $this->requests->submit($request, $requester);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('leave_type', $exception->errors());
            $this->assertArrayHasKey('handover', $exception->errors());
        }

        $this->assertSame(RequestStatus::Draft, $request->refresh()->status);
        $this->assertNull($request->workflowInstance);
    }

    public function test_submission_is_blocked_when_a_required_attachment_is_missing(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        $request = $this->requests->save($requester, RequestType::where('code', 'REIMB')->firstOrFail(), [
            'title' => 'Taxi fares for the client workshop',
            'form_data' => [
                'expense_category' => 'Travel',
                'amount' => '450000',
                'expense_date' => '2026-11-10',
                'cost_centre' => 'HR-OPS-24',
            ],
        ]);

        try {
            $this->requests->submit($request, $requester);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('files.receipt', $exception->errors());
        }

        $this->assertSame(RequestStatus::Draft, $request->refresh()->status);
    }

    public function test_a_request_cannot_be_submitted_twice(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        $request = $this->requests->save($requester, RequestType::where('code', 'LEAVE')->firstOrFail(), $this->leavePayload());
        $this->requests->submit($request, $requester);

        $this->expectException(ValidationException::class);
        $this->requests->submit($request->refresh(), $requester);
    }

    public function test_a_non_requester_cannot_submit_a_draft(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        $request = $this->requests->save($requester, RequestType::where('code', 'LEAVE')->firstOrFail(), $this->leavePayload());

        $this->expectException(AuthorizationException::class);
        $this->requests->submit($request, $this->user('maya@arunika.test'));
    }

    public function test_cancelling_an_open_request_cancels_the_pending_task(): void
    {
        $requester = $this->user('ferdinand@arunika.test');
        $request = ServiceRequest::where('request_number', 'REQ-202611-0004')->firstOrFail();

        $this->requests->cancel($request, $requester, 'Travel plans changed.');

        $request->refresh();
        $this->assertSame(RequestStatus::Cancelled, $request->status);
        $this->assertSame(InstanceStatus::Cancelled, $request->workflowInstance->status);
        $this->assertSame(0, $request->workflowInstance->tasks()->where('status', 'pending')->count());
        $this->assertSame(1, $request->workflowInstance->tasks()->where('status', 'cancelled')->count());
    }

    public function test_a_revised_request_returns_to_the_stage_that_asked_for_changes(): void
    {
        $requester = $this->user('ferdinand@arunika.test');
        $request = ServiceRequest::where('request_number', 'REQ-202611-0003')->firstOrFail();

        $this->assertSame(RequestStatus::RevisionRequired, $request->status);

        $resubmitted = $this->requests->submit($request, $requester);

        $this->assertSame(RequestStatus::UnderReview, $resubmitted->status);
        $this->assertSame(1, $resubmitted->workflowInstance->current_stage_order);

        $pending = $resubmitted->workflowInstance->tasks()->where('status', 'pending')->sole();
        $this->assertSame('andi@arunika.test', $pending->approver->email);
        $this->assertDatabaseHas('audit_logs', ['action' => 'request.resubmitted']);
    }

    public function test_a_closed_request_cannot_be_cancelled(): void
    {
        $requester = $this->user('ferdinand@arunika.test');
        $request = ServiceRequest::where('request_number', 'REQ-202611-0001')->firstOrFail();

        $this->expectException(ValidationException::class);
        $this->requests->cancel($request, $requester);
    }
}
