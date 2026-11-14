<?php

namespace App\Livewire;

use App\Exceptions\WorkflowException;
use App\Models\ApprovalTask;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Approval decision')]
class ApprovalDetail extends Component
{
    public ApprovalTask $task;

    public string $comment = '';

    /** @var string|null approve|reject|revision */
    public ?string $pendingAction = null;

    public function mount(ApprovalTask $task): void
    {
        Gate::authorize('view', $task);

        $this->task = $task->load([
            'stage.approverRole',
            'workflowInstance.request.requestType',
            'workflowInstance.request.formTemplate',
            'workflowInstance.request.requester.department',
            'workflowInstance.request.department',
            'workflowInstance.request.attachments.uploader',
            'workflowInstance.workflowTemplate.stages.approverRole',
            'workflowInstance.tasks.stage',
            'workflowInstance.tasks.approver',
            'workflowInstance.tasks.histories.actor',
        ]);
    }

    public function openDecision(string $action): void
    {
        Gate::authorize('decide', $this->task);

        if (! in_array($action, ['approve', 'reject', 'revision'], true)) {
            return;
        }

        $this->pendingAction = $action;
        $this->comment = '';
        $this->resetErrorBag();
    }

    public function closeDecision(): void
    {
        $this->pendingAction = null;
        $this->comment = '';
        $this->resetErrorBag();
    }

    public function confirmDecision(ApprovalService $service): void
    {
        Gate::authorize('decide', $this->task);

        if ($this->pendingAction === null) {
            return;
        }

        try {
            $service->decide($this->task, auth()->user(), $this->pendingAction, $this->comment ?: null);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key === 'comment' ? 'comment' : $key, $message);
                }
            }

            return;
        } catch (WorkflowException $exception) {
            $this->addError('decision', $exception->getMessage());

            return;
        }

        $labels = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'revision' => 'returned for revision',
        ];

        session()->flash('status', "{$this->task->workflowInstance->request->request_number} was {$labels[$this->pendingAction]}.");

        $this->redirect(route('approvals.index'), navigate: true);
    }

    public function render()
    {
        $request = $this->task->workflowInstance->request;
        $instance = $this->task->workflowInstance;
        $fields = $request->formTemplate?->fields() ?? [];
        $decisions = $instance->tasks->flatMap->histories->sortBy('created_at');

        return view('livewire.approval-detail', [
            'serviceRequest' => $request,
            'instance' => $instance,
            'fields' => $fields,
            'decisions' => $decisions,
            'canDecide' => Gate::allows('decide', $this->task),
        ]);
    }
}
