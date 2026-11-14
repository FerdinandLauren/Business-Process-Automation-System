<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Models\ApprovalTask;
use App\Models\RequestType;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Approval inbox')]
class ApprovalInbox extends Component
{
    use WithPagination;

    #[Url(as: 'view', history: true)]
    public string $view = 'pending';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'type', history: true)]
    public string $type = '';

    public function mount(): void
    {
        Gate::authorize('process-approvals');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['view', 'search', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $userId = auth()->id();

        $base = ApprovalTask::query()->where('assigned_approver_id', $userId);

        $counts = [
            'pending' => (clone $base)->where('status', TaskStatus::Pending->value)->count(),
            'overdue' => (clone $base)->where('status', TaskStatus::Pending->value)
                ->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'completed' => (clone $base)->whereIn('status', [
                TaskStatus::Approved->value,
                TaskStatus::Rejected->value,
                TaskStatus::RevisionRequested->value,
            ])->count(),
        ];

        $tasks = (clone $base)
            ->with([
                'stage',
                'workflowInstance.request.requestType',
                'workflowInstance.request.requester',
                'workflowInstance.request.department',
            ])
            ->when($this->view === 'pending', fn ($query) => $query->where('status', TaskStatus::Pending->value))
            ->when($this->view === 'overdue', fn ($query) => $query
                ->where('status', TaskStatus::Pending->value)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()))
            ->when($this->view === 'completed', fn ($query) => $query->whereIn('status', [
                TaskStatus::Approved->value,
                TaskStatus::Rejected->value,
                TaskStatus::RevisionRequested->value,
            ]))
            ->when($this->search !== '', function ($query) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->search).'%';
                $query->whereHas('workflowInstance.request', fn ($inner) => $inner
                    ->where('request_number', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', $like)));
            })
            ->when($this->type !== '', fn ($query) => $query->whereHas(
                'workflowInstance.request.requestType',
                fn ($inner) => $inner->where('code', $this->type),
            ))
            ->when($this->view === 'pending', fn ($query) => $query->orderByRaw('due_at IS NULL')->orderBy('due_at'))
            ->when($this->view !== 'pending', fn ($query) => $query->orderByDesc('completed_at')->orderByDesc('id'))
            ->paginate(15);

        return view('livewire.approval-inbox', [
            'tasks' => $tasks,
            'counts' => $counts,
            'types' => RequestType::query()->orderBy('name')->get(),
        ]);
    }
}
