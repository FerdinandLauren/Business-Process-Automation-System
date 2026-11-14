<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\RequestStatus;
use App\Models\ApprovalHistory;
use App\Models\ApprovalTask;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Process an approval decision atomically.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws \App\Exceptions\WorkflowException
     */
    public function decide(ApprovalTask $task, User $actor, string $action, ?string $comment = null): ApprovalTask
    {
        $action = $this->normalizeAction($action);

        if (in_array($action, ['rejected', 'revision_requested'], true) && mb_strlen(trim((string) $comment)) < 5) {
            throw ValidationException::withMessages([
                'comment' => $action === 'rejected'
                    ? 'A rejection reason of at least 5 characters is required.'
                    : 'Please explain what the requester needs to revise (at least 5 characters).',
            ]);
        }

        return DB::transaction(function () use ($task, $actor, $action, $comment) {
            /** @var ApprovalTask $locked */
            $locked = ApprovalTask::query()
                ->whereKey($task->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->assigned_approver_id !== (int) $actor->getKey()) {
                throw new AuthorizationException('This approval task is not assigned to you.');
            }

            if (! $locked->isPending()) {
                throw new \App\Exceptions\WorkflowException('This task has already been decided.');
            }

            $instance = $locked->workflowInstance()->with('request', 'workflowTemplate')->firstOrFail();
            $request = $instance->request;

            if ($request->status !== RequestStatus::UnderReview || ! $instance->isRunning()) {
                throw new \App\Exceptions\WorkflowException('This request is not awaiting a decision.');
            }

            $updated = ApprovalTask::query()
                ->whereKey($locked->getKey())
                ->where('status', \App\Enums\TaskStatus::Pending->value)
                ->update([
                    'status' => $action,
                    'completed_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new \App\Exceptions\WorkflowException('This task was processed by another action. No changes were made.');
            }

            ApprovalHistory::create([
                'approval_task_id' => $locked->getKey(),
                'actor_id' => $actor->getKey(),
                'action' => $action,
                'comment' => $comment,
            ]);

            $locked->refresh()->load('stage', 'workflowInstance.request');

            match ($action) {
                'approved' => $this->handleApproved($locked),
                'rejected' => $this->handleRejected($locked, $comment ?? ''),
                default => $this->handleRevision($locked, $comment ?? ''),
            };

            return $locked;
        });
    }

    public function approve(ApprovalTask $task, User $actor, ?string $comment = null): ApprovalTask
    {
        return $this->decide($task, $actor, 'approved', $comment);
    }

    public function reject(ApprovalTask $task, User $actor, string $comment): ApprovalTask
    {
        return $this->decide($task, $actor, 'rejected', $comment);
    }

    public function requestRevision(ApprovalTask $task, User $actor, string $comment): ApprovalTask
    {
        return $this->decide($task, $actor, 'revision_requested', $comment);
    }

    private function handleApproved(ApprovalTask $task): void
    {
        $request = $task->workflowInstance->request;

        $this->engine->advance($task);

        $this->audit->log('approval.approved', $request, [
            'request_number' => $request->request_number,
            'stage' => $task->stage->stage_name,
            'task_id' => $task->getKey(),
        ]);
    }

    private function handleRejected(ApprovalTask $task, string $comment): void
    {
        $instance = $task->workflowInstance;
        $request = $instance->request;

        $this->engine->terminate($instance, RequestStatus::Rejected, $comment);

        $this->notifications->notify(
            $request->requester,
            'request.rejected',
            'Request rejected',
            "{$request->request_number} was rejected at \"{$task->stage->stage_name}\". Reason: {$comment}",
            $request,
        );

        $this->audit->log('approval.rejected', $request, [
            'request_number' => $request->request_number,
            'stage' => $task->stage->stage_name,
            'reason' => $comment,
        ]);
    }

    private function handleRevision(ApprovalTask $task, string $comment): void
    {
        $instance = $task->workflowInstance;
        $request = $instance->request;

        $this->engine->markRevisionRequired($instance, $comment);

        $this->notifications->notify(
            $request->requester,
            'request.revision_required',
            'Revision requested',
            "{$request->request_number} needs changes before \"{$task->stage->stage_name}\" can continue. Note: {$comment}",
            $request,
        );

        $this->audit->log('approval.revision_requested', $request, [
            'request_number' => $request->request_number,
            'stage' => $task->stage->stage_name,
            'note' => $comment,
        ]);
    }

    private function normalizeAction(string $action): string
    {
        return match ($action) {
            'approve', 'approved' => 'approved',
            'reject', 'rejected' => 'rejected',
            'revision', 'request_revision', 'revision_requested' => 'revision_requested',
            default => throw new \InvalidArgumentException("Unsupported approval action [{$action}]."),
        };
    }

    /**
     * Priority is used for inbox ordering only.
     */
    public function priorityWeight(ServiceRequest $request): int
    {
        return $request->priority instanceof Priority ? $request->priority->weight() : 0;
    }
}
