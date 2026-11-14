<?php

namespace App\Services;

use App\Enums\ApproverScope;
use App\Enums\InstanceStatus;
use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalTask;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkflowEvent;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowEngine
{
    public function __construct(
        private readonly SLAService $sla,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create the workflow instance and its first approval task.
     * Must be called inside an open database transaction.
     */
    public function start(ServiceRequest $request): WorkflowInstance
    {
        $template = $request->requestType->activeWorkflowTemplate();

        if ($template === null) {
            throw WorkflowException::noActiveTemplate($request->requestType->name);
        }

        $stages = $this->activeStages($template);

        if ($stages->isEmpty()) {
            throw WorkflowException::noActiveTemplate($request->requestType->name);
        }

        $first = $stages->first();
        $approver = $this->resolveApprover($first, $request);

        if ($approver === null) {
            throw WorkflowException::noEligibleApprover($first->stage_name);
        }

        $instance = WorkflowInstance::create([
            'request_id' => $request->getKey(),
            'workflow_template_id' => $template->getKey(),
            'workflow_version' => $template->version,
            'current_stage_order' => $first->stage_order,
            'status' => InstanceStatus::Running,
            'started_at' => now(),
        ]);

        $request->forceFill([
            'status' => RequestStatus::UnderReview,
            'current_stage_order' => $first->stage_order,
        ])->save();

        $this->createTask($instance, $request, $first, $approver);
        $this->recordEvent($instance, 'workflow.started', [
            'workflow_template_id' => $template->getKey(),
            'workflow_version' => $template->version,
            'stage_order' => $first->stage_order,
        ]);

        return $instance;
    }

    /**
     * Restart the approval step that asked for a revision. The requester's
     * revision time is not counted against internal processing SLA.
     */
    public function resumeAfterRevision(ServiceRequest $request): ApprovalTask
    {
        $instance = $request->workflowInstance()->first();

        if ($instance === null || ! $instance->isRunning()) {
            throw WorkflowException::invalidTransition('This request has no running workflow to resume.');
        }

        $stage = $instance->workflowTemplate->stages()
            ->where('stage_order', $instance->current_stage_order)
            ->where('is_active', true)
            ->first();

        if ($stage === null) {
            throw WorkflowException::invalidTransition('The stage that requested the revision is no longer available.');
        }

        $approver = $this->resolveApprover($stage, $request);

        if ($approver === null) {
            throw WorkflowException::noEligibleApprover($stage->stage_name);
        }

        $request->forceFill([
            'status' => RequestStatus::UnderReview,
            'current_stage_order' => $stage->stage_order,
        ])->save();

        $task = $this->createTask($instance, $request, $stage, $approver);
        $this->recordEvent($instance, 'workflow.resumed', ['stage_order' => $stage->stage_order, 'task_id' => $task->getKey()]);

        return $task;
    }

    /**
     * Move the workflow forward after a positive decision.
     */
    public function advance(ApprovalTask $task): void
    {
        $instance = $task->workflowInstance;
        $request = $instance->request;
        $template = $instance->workflowTemplate;
        $stages = $this->activeStages($template);

        $next = $stages->first(fn (WorkflowStage $stage) => $stage->stage_order > $task->stage->stage_order);

        if ($next === null) {
            $this->complete($instance);

            return;
        }

        $approver = $this->resolveApprover($next, $request);

        if ($approver === null) {
            // The final decision was already recorded; the workflow cannot advance and
            // must not be silently completed. Surface a hard failure for the transaction.
            throw WorkflowException::noEligibleApprover($next->stage_name);
        }

        $instance->forceFill(['current_stage_order' => $next->stage_order])->save();
        $request->forceFill(['current_stage_order' => $next->stage_order])->save();

        $nextTask = $this->createTask($instance, $request, $next, $approver);

        $this->recordEvent($instance, 'workflow.stage_advanced', [
            'from_stage_order' => $task->stage->stage_order,
            'to_stage_order' => $next->stage_order,
            'task_id' => $nextTask->getKey(),
        ]);

        $this->notifications->notify(
            $request->requester,
            'request.approved_stage',
            'Request moved to next stage',
            "{$request->request_number} passed \"{$task->stage->stage_name}\" and is now with {$next->stage_name}.",
            $request,
        );
    }

    /**
     * Complete a workflow after the last stage was approved.
     */
    public function complete(WorkflowInstance $instance): void
    {
        $request = $instance->request;

        $instance->forceFill([
            'status' => InstanceStatus::Completed,
            'completed_at' => now(),
            'current_stage_order' => null,
        ])->save();

        $request->forceFill([
            'status' => RequestStatus::Approved,
            'completed_at' => now(),
            'current_stage_order' => null,
        ])->save();

        $this->recordEvent($instance, 'workflow.completed', ['request_number' => $request->request_number]);

        $this->notifications->notify(
            $request->requester,
            'request.completed',
            'Request approved',
            "{$request->request_number} has completed all approval stages.",
            $request,
        );

        $this->audit->log('request.approved', $request, [
            'request_number' => $request->request_number,
        ]);
    }

    /**
     * Stop a workflow because of rejection or cancellation.
     */
    public function terminate(WorkflowInstance $instance, RequestStatus $requestStatus, string $reason, ?User $actor = null): void
    {
        $request = $instance->request;

        $instance->forceFill([
            'status' => $requestStatus === RequestStatus::Cancelled ? InstanceStatus::Cancelled : InstanceStatus::Rejected,
            'completed_at' => now(),
        ])->save();

        $request->forceFill([
            'status' => $requestStatus,
            'completed_at' => now(),
        ])->save();

        $instance->tasks()
            ->where('status', TaskStatus::Pending->value)
            ->update(['status' => TaskStatus::Cancelled->value, 'completed_at' => now()]);

        $this->recordEvent($instance, 'workflow.terminated', [
            'status' => $requestStatus->value,
            'reason' => $reason,
        ]);

        $this->audit->log(
            $requestStatus === RequestStatus::Cancelled ? 'request.cancelled' : 'request.rejected',
            $request,
            ['request_number' => $request->request_number, 'reason' => $reason],
            $actor,
        );
    }

    /**
     * Flag the workflow as waiting on the requester.
     */
    public function markRevisionRequired(WorkflowInstance $instance, string $comment): void
    {
        $request = $instance->request;

        $request->forceFill(['status' => RequestStatus::RevisionRequired])->save();

        $this->recordEvent($instance, 'workflow.revision_requested', ['comment' => $comment]);
    }

    /**
     * @return Collection<int, WorkflowStage>
     */
    public function activeStages(WorkflowTemplate $template): Collection
    {
        return $template->stages()
            ->where('is_active', true)
            ->orderBy('stage_order')
            ->get();
    }

    public function resolveApprover(WorkflowStage $stage, ServiceRequest $request): ?User
    {
        if ($stage->approver_scope === ApproverScope::SpecificUser) {
            return User::query()
                ->active()
                ->whereKey($stage->specific_user_id)
                ->first();
        }

        $query = User::query()
            ->active()
            ->whereHas('roles', fn ($roles) => $roles->whereKey($stage->approver_role_id));

        if ($stage->approver_scope === ApproverScope::RequesterDepartment) {
            $query->where('department_id', $request->department_id);
        }

        $candidates = $query->orderBy('id')->get();

        if ($candidates->count() > 1) {
            $candidates = $candidates->reject(fn (User $user) => $user->is($request->requester));
        }

        return $candidates->first();
    }

    public function createTask(
        WorkflowInstance $instance,
        ServiceRequest $request,
        WorkflowStage $stage,
        User $approver,
    ): ApprovalTask {
        $startedAt = now();

        $task = ApprovalTask::create([
            'workflow_instance_id' => $instance->getKey(),
            'workflow_stage_id' => $stage->getKey(),
            'assigned_approver_id' => $approver->getKey(),
            'status' => TaskStatus::Pending,
            'started_at' => $startedAt,
            'due_at' => $this->sla->addBusinessHours($startedAt, $stage->sla_hours),
        ]);

        $this->recordEvent($instance, 'task.created', [
            'task_id' => $task->getKey(),
            'stage_order' => $stage->stage_order,
            'approver_id' => $approver->getKey(),
            'due_at' => $task->due_at?->toIso8601String(),
        ]);

        $this->notifications->notify(
            $approver,
            'task.assigned',
            'Approval required',
            "{$request->request_number} · {$request->title} is waiting for your decision at \"{$stage->stage_name}\".",
            $request,
            route('approvals.show', $task),
        );

        return $task;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordEvent(WorkflowInstance $instance, string $type, array $payload = []): void
    {
        WorkflowEvent::create([
            'workflow_instance_id' => $instance->getKey(),
            'event_type' => $type,
            'payload' => $payload ?: null,
            'occurred_at' => now(),
        ]);
    }
}
