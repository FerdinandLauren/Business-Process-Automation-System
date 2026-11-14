<?php

namespace App\Console\Commands;

use App\Models\ApprovalTask;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\SLAService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorSlaCommand extends Command
{
    protected $signature = 'bpa:sla-monitor
                            {--dry-run : Report what would happen without sending notifications}';

    protected $description = 'Send due-soon reminders and record SLA breaches for pending approval tasks';

    public function handle(
        SLAService $sla,
        NotificationService $notifications,
        AuditLogger $audit,
    ): int {
        $hours = (int) config('bpa.sla.reminder_hours_before', 4);
        $dryRun = (bool) $this->option('dry-run');

        $dueSoon = ApprovalTask::query()
            ->dueSoon($hours)
            ->with(['approver', 'workflowInstance.request.requestType', 'stage'])
            ->get();

        $breached = ApprovalTask::query()
            ->breached()
            ->with(['approver', 'workflowInstance.request.requestType', 'workflowInstance.request.requester', 'stage'])
            ->get();

        if ($dryRun) {
            $rows = $dueSoon->map(fn (ApprovalTask $task) => [
                $task->getKey(),
                $task->workflowInstance?->request?->request_number ?? '—',
                $task->approver?->name ?? '—',
                $task->due_at?->format('d M Y H:i') ?? '—',
                'remind',
            ])->all();

            $rows = array_merge($rows, $breached->map(fn (ApprovalTask $task) => [
                $task->getKey(),
                $task->workflowInstance?->request?->request_number ?? '—',
                $task->approver?->name ?? '—',
                $task->due_at?->format('d M Y H:i') ?? '—',
                'mark breached',
            ])->all());

            $this->table(['Task', 'Request', 'Approver', 'Due', 'Action'], $rows);

            $this->info("Dry run: {$dueSoon->count()} reminder(s), {$breached->count()} breach(es) would be processed.");

            return self::SUCCESS;
        }

        $reminded = 0;
        $recorded = 0;

        foreach ($dueSoon as $task) {
            $reminded += DB::transaction(function () use ($task, $notifications, $sla) {
                $claimed = ApprovalTask::query()
                    ->whereKey($task->getKey())
                    ->whereNull('reminder_sent_at')
                    ->update(['reminder_sent_at' => now()]);

                if ($claimed === 0) {
                    return 0;
                }

                $request = $task->workflowInstance?->request;

                if ($request === null || $task->approver === null) {
                    return 0;
                }

                $notifications->notify(
                    $task->approver,
                    'sla.reminder',
                    'Approval due soon',
                    "{$request->request_number} · {$task->stage?->stage_name} needs a decision. {$sla->humanizeRemaining($task->due_at)}.",
                    $request,
                );

                return 1;
            });
        }

        foreach ($breached as $task) {
            $recorded += DB::transaction(function () use ($task, $notifications, $audit) {
                $claimed = ApprovalTask::query()
                    ->whereKey($task->getKey())
                    ->whereNull('breached_at')
                    ->update(['breached_at' => now()]);

                if ($claimed === 0) {
                    return 0;
                }

                $request = $task->workflowInstance?->request;

                if ($request === null) {
                    return 0;
                }

                $audit->log('approval.sla_breached', $task, [
                    'request' => $request->request_number,
                    'stage' => $task->stage?->stage_name,
                    'approver' => $task->approver?->name,
                    'due_at' => $task->due_at?->toIso8601String(),
                ]);

                if ($task->approver !== null) {
                    $notifications->notify(
                        $task->approver,
                        'sla.breached',
                        'Approval is overdue',
                        "{$request->request_number} · {$task->stage?->stage_name} passed its SLA. Decide or ask the requester for revision.",
                        $request,
                    );
                }

                if ($request->requester !== null) {
                    $notifications->notify(
                        $request->requester,
                        'sla.breached',
                        'Your request is waiting past its SLA',
                        "{$request->request_number} is still waiting on {$task->stage?->stage_name}. You can see the current owner in the request timeline.",
                        $request,
                    );
                }

                return 1;
            });
        }

        $this->info("Reminders sent: {$reminded}. SLA breaches recorded: {$recorded}.");

        return self::SUCCESS;
    }
}
