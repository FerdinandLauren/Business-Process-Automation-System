<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use App\Models\ApprovalTask;
use App\Models\Department;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    public const REPORTS = [
        'requests' => [
            'label' => 'Request summary',
            'description' => 'Submitted requests, their outcome and how long they took end to end.',
        ],
        'approvals' => [
            'label' => 'Approval summary',
            'description' => 'Every decision task that entered an approver queue in the period.',
        ],
        'sla' => [
            'label' => 'SLA performance',
            'description' => 'On-time decisions, overdue tasks and compliance by approval stage.',
        ],
        'departments' => [
            'label' => 'Department activity',
            'description' => 'How each department raises and closes requests.',
        ],
        'statuses' => [
            'label' => 'Status distribution',
            'description' => 'Where the filtered requests currently sit in the process.',
        ],
    ];

    private const OPEN_STATUSES = [
        RequestStatus::Submitted->value,
        RequestStatus::UnderReview->value,
        RequestStatus::RevisionRequired->value,
    ];

    private const DECIDED_STATUSES = [
        TaskStatus::Approved->value,
        TaskStatus::Rejected->value,
        TaskStatus::RevisionRequested->value,
    ];

    public function __construct(private readonly SLAService $sla) {}

    /**
     * Normalise validated input into the filter set used by every report.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function filters(array $input): array
    {
        $report = $input['report'] ?? 'requests';

        if (! is_string($report) || ! array_key_exists($report, self::REPORTS)) {
            $report = 'requests';
        }

        return [
            'report' => $report,
            'from' => $input['from'] ?? now()->subDays(30)->toDateString(),
            'to' => $input['to'] ?? now()->toDateString(),
            'department' => $input['department'] ?? null,
            'type' => $input['type'] ?? null,
            'status' => $input['status'] ?? null,
        ];
    }

    /**
     * Status options depend on whether the report measures requests or approval tasks.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function statusOptions(string $report): array
    {
        return in_array($report, ['approvals', 'sla'], true)
            ? TaskStatus::options()
            : RequestStatus::options();
    }

    /**
     * Everything the report view needs: stat cards, columns, rows and an optional paginator.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function payload(string $report, array $filters): array
    {
        return match ($report) {
            'approvals' => $this->approvalPayload($filters),
            'sla' => $this->slaPayload($filters),
            'departments' => $this->departmentPayload($filters),
            'statuses' => $this->statusPayload($filters),
            default => $this->requestPayload($filters),
        };
    }

    /**
     * @return array<int, string>
     */
    public function csvHeader(string $report): array
    {
        return match ($report) {
            'approvals' => ['Request number', 'Request title', 'Stage', 'Approver', 'Task status', 'Received', 'Decided', 'Decision (business hours)', 'SLA'],
            'sla' => ['Stage', 'Tasks', 'On time', 'Late', 'Open', 'Compliance %', 'Average decision (business hours)'],
            'departments' => ['Department', 'Raised', 'Approved', 'Rejected', 'Open', 'Average cycle (business hours)'],
            'statuses' => ['Status', 'Requests', 'Share %'],
            default => ['Request number', 'Title', 'Type', 'Department', 'Requester', 'Status', 'Priority', 'Submitted', 'Completed', 'Cycle (business hours)'],
        };
    }

    /**
     * Rows for the CSV export. List reports stream in chunks so a large period does not exhaust memory.
     *
     * @param  array<string, mixed>  $filters
     * @return iterable<int, array<int, string>>
     */
    public function csvRows(string $report, array $filters): iterable
    {
        return match ($report) {
            'approvals' => $this->approvalCsv($filters),
            'sla' => $this->slaCsv($filters),
            'departments' => $this->departmentCsv($filters),
            'statuses' => $this->statusCsv($filters),
            default => $this->requestCsv($filters),
        };
    }

    public function hoursLabel(?float $hours): string
    {
        if ($hours === null) {
            return '—';
        }

        return $hours < 0.1 ? '<0.1h' : number_format($hours, 1).'h';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function requestBase(array $filters): Builder
    {
        return ServiceRequest::query()
            ->with(['requestType', 'requester', 'department'])
            ->whereNotNull('submitted_at')
            ->whereDate('submitted_at', '>=', $filters['from'])
            ->whereDate('submitted_at', '<=', $filters['to'])
            ->when($filters['department'], fn (Builder $query, $id) => $query->where('department_id', (int) $id))
            ->when($filters['type'], fn (Builder $query, $code) => $query->whereHas(
                'requestType',
                fn (Builder $inner) => $inner->where('code', $code),
            ))
            ->when($filters['status'], fn (Builder $query, $status) => $query->where('status', $status));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function taskBase(array $filters): Builder
    {
        return ApprovalTask::query()
            ->with([
                'stage',
                'approver',
                'workflowInstance.request.requestType',
                'workflowInstance.request.requester',
                'workflowInstance.request.department',
            ])
            ->whereNotNull('started_at')
            ->whereDate('started_at', '>=', $filters['from'])
            ->whereDate('started_at', '<=', $filters['to'])
            ->whereHas('workflowInstance.request', function (Builder $query) use ($filters) {
                $query
                    ->when($filters['department'], fn (Builder $inner, $id) => $inner->where('department_id', (int) $id))
                    ->when($filters['type'], fn (Builder $inner, $code) => $inner->whereHas(
                        'requestType',
                        fn (Builder $deep) => $deep->where('code', $code),
                    ));
            })
            ->when($filters['status'], fn (Builder $query, $status) => $query->where('status', $status));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function requestPayload(array $filters): array
    {
        $base = $this->requestBase($filters);

        $total = (clone $base)->count();
        $approved = (clone $base)->where('status', RequestStatus::Approved->value)->count();
        $open = (clone $base)->whereIn('status', self::OPEN_STATUSES)->count();

        $durations = (clone $base)
            ->whereNotNull('completed_at')
            ->get(['submitted_at', 'completed_at'])
            ->map(fn (ServiceRequest $request) => $this->businessHours($request->submitted_at, $request->completed_at));

        $average = $this->average($durations->all());
        $requests = (clone $base)->orderByDesc('submitted_at')->paginate(20)->withQueryString();

        return [
            'stats' => [
                ['label' => 'Submitted', 'value' => number_format($total), 'meta' => 'In the selected period'],
                ['label' => 'Approved', 'value' => number_format($approved), 'meta' => $this->share($approved, $total).' of submissions', 'tone' => 'positive'],
                ['label' => 'Still open', 'value' => number_format($open), 'meta' => $open > 0 ? 'Waiting on a decision' : 'Nothing in flight', 'tone' => $open > 0 ? 'caution' : 'default'],
                ['label' => 'Average cycle', 'value' => $this->hoursLabel($average), 'meta' => 'Business hours, submission to completion'],
            ],
            'columns' => [
                ['label' => 'Request'],
                ['label' => 'Type'],
                ['label' => 'Department'],
                ['label' => 'Requester'],
                ['label' => 'Status'],
                ['label' => 'Submitted'],
                ['label' => 'Completed'],
                ['label' => 'Cycle', 'align' => 'right'],
            ],
            'rows' => $requests->getCollection()->map(fn (ServiceRequest $request) => [
                ['primary' => $request->title, 'secondary' => $request->request_number, 'href' => route('requests.show', $request)],
                $request->requestType?->name ?? '—',
                $request->department?->name ?? '—',
                $request->requester?->name ?? '—',
                ['label' => $request->status->label(), 'tone' => $request->status->tone()],
                ['value' => $request->submitted_at?->format('d M Y') ?? '—', 'mono' => true],
                ['value' => $request->completed_at?->format('d M Y') ?? '—', 'mono' => true],
                ['value' => $this->hoursLabel($this->businessHours($request->submitted_at, $request->completed_at)), 'mono' => true],
            ])->all(),
            'paginator' => $requests,
            'empty' => [
                'title' => 'No requests in this period',
                'description' => 'Widen the date range or clear the department, type and status filters.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function approvalPayload(array $filters): array
    {
        $base = $this->taskBase($filters);

        $total = (clone $base)->count();
        $decided = (clone $base)->whereIn('status', self::DECIDED_STATUSES)->count();
        $pending = (clone $base)->where('status', TaskStatus::Pending->value)->count();

        $durations = (clone $base)
            ->whereIn('status', self::DECIDED_STATUSES)
            ->whereNotNull('completed_at')
            ->get(['started_at', 'completed_at'])
            ->map(fn (ApprovalTask $task) => $this->businessHours($task->started_at, $task->completed_at));

        $average = $this->average($durations->all());
        $tasks = (clone $base)->orderByDesc('started_at')->paginate(20)->withQueryString();

        return [
            'stats' => [
                ['label' => 'Tasks', 'value' => number_format($total), 'meta' => 'Reached an approver in the period'],
                ['label' => 'Decided', 'value' => number_format($decided), 'meta' => $this->share($decided, $total).' of tasks', 'tone' => 'positive'],
                ['label' => 'Still pending', 'value' => number_format($pending), 'meta' => $pending > 0 ? 'Sitting in a queue now' : 'No open tasks', 'tone' => $pending > 0 ? 'caution' : 'default'],
                ['label' => 'Average decision', 'value' => $this->hoursLabel($average), 'meta' => 'Business hours, received to decided'],
            ],
            'columns' => [
                ['label' => 'Request'],
                ['label' => 'Stage'],
                ['label' => 'Approver'],
                ['label' => 'Status'],
                ['label' => 'Received'],
                ['label' => 'Decided'],
                ['label' => 'Decision', 'align' => 'right'],
                ['label' => 'SLA', 'align' => 'right'],
            ],
            'rows' => $tasks->getCollection()->map(function (ApprovalTask $task) {
                $request = $task->workflowInstance?->request;

                return [
                    ['primary' => $request?->title ?? 'Request unavailable', 'secondary' => $request?->request_number ?? '—', 'href' => $request ? route('requests.show', $request) : null],
                    $task->stage?->stage_name ?? '—',
                    $task->approver?->name ?? '—',
                    ['label' => $task->status->label(), 'tone' => $task->status->tone()],
                    ['value' => $task->started_at?->format('d M Y') ?? '—', 'mono' => true],
                    ['value' => $task->completed_at?->format('d M Y') ?? '—', 'mono' => true],
                    ['value' => $this->hoursLabel($this->businessHours($task->started_at, $task->completed_at)), 'mono' => true],
                    $this->slaCell($task),
                ];
            })->all(),
            'paginator' => $tasks,
            'empty' => [
                'title' => 'No approval tasks in this period',
                'description' => 'Only tasks whose stage started inside the selected dates appear here.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function slaPayload(array $filters): array
    {
        $tasks = (clone $this->taskBase($filters))->get();

        $rows = $tasks
            ->groupBy(fn (ApprovalTask $task) => $task->stage?->stage_name ?? 'Unassigned stage')
            ->map(function (Collection $group, string $stage) {
                $onTime = 0;
                $late = 0;
                $open = 0;
                $durations = [];

                foreach ($group as $task) {
                    $duration = $this->businessHours($task->started_at, $task->completed_at);

                    if ($duration !== null) {
                        $durations[] = $duration;
                    }

                    if ($task->completed_at !== null) {
                        if ($task->due_at === null) {
                            continue;
                        }

                        $task->completed_at->lessThanOrEqualTo($task->due_at) ? $onTime++ : $late++;
                    } elseif ($task->due_at !== null && $task->due_at->isPast()) {
                        $late++;
                    } else {
                        $open++;
                    }
                }

                $measured = $onTime + $late;

                return [
                    'stage' => $stage,
                    'tasks' => $group->count(),
                    'on_time' => $onTime,
                    'late' => $late,
                    'open' => $open,
                    'compliance' => $measured > 0 ? $onTime / $measured * 100 : null,
                    'average' => $this->average($durations),
                ];
            })
            ->sortByDesc('late')
            ->values();

        $onTime = $rows->sum('on_time');
        $late = $rows->sum('late');
        $measured = $onTime + $late;
        $compliance = $measured > 0 ? $onTime / $measured * 100 : null;

        return [
            'stats' => [
                ['label' => 'Tasks measured', 'value' => number_format($rows->sum('tasks')), 'meta' => 'Across '.$rows->count().' '.str('stage')->plural($rows->count())],
                ['label' => 'On time', 'value' => number_format($onTime), 'meta' => 'Decided before the stage deadline', 'tone' => 'positive'],
                ['label' => 'Late', 'value' => number_format($late), 'meta' => 'Decided late or still past due', 'tone' => $late > 0 ? 'danger' : 'default'],
                ['label' => 'Compliance', 'value' => $compliance === null ? '—' : number_format($compliance, 1).'%', 'meta' => 'On time against decided tasks', 'tone' => $compliance === null ? 'default' : ($compliance >= 90 ? 'positive' : 'caution')],
            ],
            'columns' => [
                ['label' => 'Stage'],
                ['label' => 'Tasks', 'align' => 'right'],
                ['label' => 'On time', 'align' => 'right'],
                ['label' => 'Late', 'align' => 'right'],
                ['label' => 'Open', 'align' => 'right'],
                ['label' => 'Compliance', 'align' => 'right'],
                ['label' => 'Avg decision', 'align' => 'right'],
            ],
            'rows' => $rows->map(fn (array $row) => [
                ['primary' => $row['stage'], 'secondary' => $row['open'].' still open'],
                ['value' => number_format($row['tasks']), 'mono' => true],
                ['value' => number_format($row['on_time']), 'mono' => true],
                ['value' => number_format($row['late']), 'mono' => true, 'tone' => $row['late'] > 0 ? 'danger' : null],
                ['value' => number_format($row['open']), 'mono' => true],
                ['value' => $row['compliance'] === null ? '—' : number_format($row['compliance'], 1).'%', 'mono' => true, 'tone' => $row['compliance'] === null ? null : ($row['compliance'] >= 90 ? 'positive' : 'caution')],
                ['value' => $this->hoursLabel($row['average']), 'mono' => true],
            ])->all(),
            'paginator' => null,
            'csv' => $rows->map(fn (array $row) => [
                $row['stage'],
                $row['tasks'],
                $row['on_time'],
                $row['late'],
                $row['open'],
                $row['compliance'] === null ? '' : number_format($row['compliance'], 1),
                $row['average'] === null ? '' : number_format($row['average'], 2),
            ])->all(),
            'empty' => [
                'title' => 'No SLA data in this period',
                'description' => 'No approval task started inside the selected dates.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function departmentPayload(array $filters): array
    {
        $requests = (clone $this->requestBase($filters))->get(['department_id', 'status', 'submitted_at', 'completed_at']);
        $names = Department::query()->orderBy('name')->pluck('name', 'id');

        $rows = $requests
            ->groupBy(fn (ServiceRequest $request) => $request->department_id)
            ->map(function (Collection $group, $departmentId) use ($names) {
                $durations = $group
                    ->whereNotNull('completed_at')
                    ->map(fn (ServiceRequest $request) => $this->businessHours($request->submitted_at, $request->completed_at));

                return [
                    'name' => $names[$departmentId] ?? 'Unassigned',
                    'raised' => $group->count(),
                    'approved' => $group->where('status', RequestStatus::Approved)->count(),
                    'rejected' => $group->where('status', RequestStatus::Rejected)->count(),
                    'open' => $group->filter(fn (ServiceRequest $request) => in_array($request->status, [
                        RequestStatus::Submitted,
                        RequestStatus::UnderReview,
                        RequestStatus::RevisionRequired,
                    ], true))->count(),
                    'average' => $this->average($durations->all()),
                ];
            })
            ->sortByDesc('raised')
            ->values();

        return [
            'stats' => [
                ['label' => 'Departments', 'value' => number_format($rows->count()), 'meta' => 'Raised at least one request'],
                ['label' => 'Requests raised', 'value' => number_format($rows->sum('raised')), 'meta' => 'In the selected period'],
                ['label' => 'Still open', 'value' => number_format($rows->sum('open')), 'meta' => 'Across every department', 'tone' => $rows->sum('open') > 0 ? 'caution' : 'default'],
                ['label' => 'Average cycle', 'value' => $this->hoursLabel($this->average($rows->pluck('average')->filter()->all())), 'meta' => 'Business hours, submission to completion'],
            ],
            'columns' => [
                ['label' => 'Department'],
                ['label' => 'Raised', 'align' => 'right'],
                ['label' => 'Approved', 'align' => 'right'],
                ['label' => 'Rejected', 'align' => 'right'],
                ['label' => 'Open', 'align' => 'right'],
                ['label' => 'Avg cycle', 'align' => 'right'],
            ],
            'rows' => $rows->map(fn (array $row) => [
                ['primary' => $row['name'], 'secondary' => $row['raised'].' '.str('request')->plural($row['raised'])],
                ['value' => number_format($row['raised']), 'mono' => true],
                ['value' => number_format($row['approved']), 'mono' => true],
                ['value' => number_format($row['rejected']), 'mono' => true, 'tone' => $row['rejected'] > 0 ? 'danger' : null],
                ['value' => number_format($row['open']), 'mono' => true],
                ['value' => $this->hoursLabel($row['average']), 'mono' => true],
            ])->all(),
            'paginator' => null,
            'csv' => $rows->map(fn (array $row) => [
                $row['name'],
                $row['raised'],
                $row['approved'],
                $row['rejected'],
                $row['open'],
                $row['average'] === null ? '' : number_format($row['average'], 2),
            ])->all(),
            'empty' => [
                'title' => 'No department activity in this period',
                'description' => 'No request was submitted inside the selected dates.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function statusPayload(array $filters): array
    {
        $counts = (clone $this->requestBase($filters))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();

        $rows = collect(RequestStatus::cases())->map(function (RequestStatus $status) use ($counts, $total) {
            $count = (int) ($counts[$status->value] ?? 0);
            $share = $total > 0 ? $count / $total * 100 : 0.0;

            return [
                'status' => $status,
                'count' => $count,
                'share' => $share,
            ];
        });

        $inProgress = $rows->filter(fn (array $row) => in_array($row['status'], [RequestStatus::Submitted, RequestStatus::UnderReview, RequestStatus::RevisionRequired], true))->sum('count');
        $closed = $rows->filter(fn (array $row) => in_array($row['status'], [RequestStatus::Rejected, RequestStatus::Cancelled], true))->sum('count');

        return [
            'stats' => [
                ['label' => 'Requests', 'value' => number_format($total), 'meta' => 'In the selected period'],
                ['label' => 'Approved', 'value' => number_format((int) ($counts[RequestStatus::Approved->value] ?? 0)), 'meta' => $this->share((int) ($counts[RequestStatus::Approved->value] ?? 0), $total).' of the total', 'tone' => 'positive'],
                ['label' => 'In progress', 'value' => number_format($inProgress), 'meta' => 'Submitted, under review or revising', 'tone' => $inProgress > 0 ? 'caution' : 'default'],
                ['label' => 'Closed without approval', 'value' => number_format($closed), 'meta' => 'Rejected or cancelled', 'tone' => $closed > 0 ? 'danger' : 'default'],
            ],
            'columns' => [
                ['label' => 'Status'],
                ['label' => 'Requests', 'align' => 'right'],
                ['label' => 'Share', 'align' => 'right'],
                ['label' => 'Distribution'],
            ],
            'rows' => $rows->map(fn (array $row) => [
                ['label' => $row['status']->label(), 'tone' => $row['status']->tone()],
                ['value' => number_format($row['count']), 'mono' => true],
                ['value' => $total > 0 ? number_format($row['share'], 1).'%' : '—', 'mono' => true],
                ['bar' => $row['share'] / 100, 'value' => $row['count'].' '.str('request')->plural($row['count'])],
            ])->all(),
            'paginator' => null,
            'csv' => $rows->map(fn (array $row) => [
                $row['status']->label(),
                $row['count'],
                $total > 0 ? number_format($row['share'], 1) : '',
            ])->all(),
            'empty' => [
                'title' => 'No requests in this period',
                'description' => 'Widen the date range or clear the filters.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<int, array<int, string>>
     */
    private function requestCsv(array $filters): iterable
    {
        return (clone $this->requestBase($filters))
            ->orderBy('submitted_at')
            ->lazy(500)
            ->map(fn (ServiceRequest $request) => [
                $request->request_number,
                $request->title,
                $request->requestType?->name ?? '',
                $request->department?->name ?? '',
                $request->requester?->name ?? '',
                $request->status->label(),
                $request->priority->label(),
                $request->submitted_at?->format('Y-m-d H:i') ?? '',
                $request->completed_at?->format('Y-m-d H:i') ?? '',
                $this->csvHours($this->businessHours($request->submitted_at, $request->completed_at)),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<int, array<int, string>>
     */
    private function approvalCsv(array $filters): iterable
    {
        return (clone $this->taskBase($filters))
            ->orderBy('started_at')
            ->lazy(500)
            ->map(function (ApprovalTask $task) {
                $request = $task->workflowInstance?->request;

                return [
                    $request?->request_number ?? '',
                    $request?->title ?? '',
                    $task->stage?->stage_name ?? '',
                    $task->approver?->name ?? '',
                    $task->status->label(),
                    $task->started_at?->format('Y-m-d H:i') ?? '',
                    $task->completed_at?->format('Y-m-d H:i') ?? '',
                    $this->csvHours($this->businessHours($task->started_at, $task->completed_at)),
                    $this->slaLabel($task),
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string>>
     */
    private function slaCsv(array $filters): array
    {
        return $this->slaPayload($filters)['csv'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string>>
     */
    private function departmentCsv(array $filters): array
    {
        return $this->departmentPayload($filters)['csv'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string>>
     */
    private function statusCsv(array $filters): array
    {
        return $this->statusPayload($filters)['csv'];
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function slaCell(ApprovalTask $task): array
    {
        $label = $this->slaLabel($task);

        $tone = match ($label) {
            'On time' => 'positive',
            'Late', 'Overdue' => 'danger',
            'Open' => 'caution',
            default => 'neutral',
        };

        return ['label' => $label, 'tone' => $tone];
    }

    private function slaLabel(ApprovalTask $task): string
    {
        if ($task->completed_at !== null) {
            if ($task->due_at === null) {
                return 'No SLA';
            }

            return $task->completed_at->lessThanOrEqualTo($task->due_at) ? 'On time' : 'Late';
        }

        if ($task->due_at !== null && $task->due_at->isPast()) {
            return 'Overdue';
        }

        return $task->due_at !== null ? 'Open' : 'No SLA';
    }

    private function businessHours(?CarbonInterface $start, ?CarbonInterface $end): ?float
    {
        if ($start === null || $end === null) {
            return null;
        }

        return $this->sla->businessMinutesBetween($start, $end) / 60;
    }

    /**
     * @param  array<int, float|null>  $durations
     */
    private function average(array $durations): ?float
    {
        $durations = array_values(array_filter($durations, fn ($value) => $value !== null));

        if ($durations === []) {
            return null;
        }

        return array_sum($durations) / count($durations);
    }

    private function share(int $part, int $total): string
    {
        return $total > 0 ? number_format($part / $total * 100, 1).'%' : '0%';
    }

    private function csvHours(?float $hours): string
    {
        return $hours === null ? '' : number_format($hours, 2);
    }
}
