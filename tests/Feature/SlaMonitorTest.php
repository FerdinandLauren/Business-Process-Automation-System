<?php

namespace Tests\Feature;

use App\Models\ApprovalTask;
use App\Models\AuditLog;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlaMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('bpa.attachments.disk'));

        $this->seed();
    }

    private function overdueTask(): ApprovalTask
    {
        return ServiceRequest::where('request_number', 'REQ-202611-0005')
            ->firstOrFail()
            ->workflowInstance
            ->tasks()
            ->where('status', 'pending')
            ->firstOrFail();
    }

    public function test_it_records_one_breach_and_notifies_both_parties(): void
    {
        $task = $this->overdueTask();

        $this->assertNull($task->breached_at);

        $notificationsBefore = \App\Models\AppNotification::count();

        $this->artisan('bpa:sla-monitor')->assertExitCode(0);

        $task->refresh();

        $this->assertNotNull($task->breached_at);
        $this->assertNull($task->reminder_sent_at);

        $this->assertSame($notificationsBefore + 2, \App\Models\AppNotification::count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $task->assigned_approver_id,
            'type' => 'sla.breached',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $task->workflowInstance->request->requester_id,
            'type' => 'sla.breached',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'approval.sla_breached',
            'entity_type' => 'ApprovalTask',
            'entity_id' => $task->getKey(),
        ]);
    }

    public function test_a_second_run_does_not_duplicate_the_breach(): void
    {
        $this->artisan('bpa:sla-monitor')->assertExitCode(0);

        $task = $this->overdueTask();
        $breachedAt = $task->breached_at;

        $notifications = \App\Models\AppNotification::count();
        $audits = AuditLog::where('action', 'approval.sla_breached')->count();

        $this->artisan('bpa:sla-monitor')->assertExitCode(0);

        $this->assertTrue($task->refresh()->breached_at->equalTo($breachedAt));
        $this->assertSame($notifications, \App\Models\AppNotification::count());
        $this->assertSame($audits, AuditLog::where('action', 'approval.sla_breached')->count());
    }

    public function test_a_task_due_within_the_reminder_window_gets_one_reminder(): void
    {
        $task = $this->overdueTask();
        $task->forceFill([
            'due_at' => now()->addHours(2),
            'reminder_sent_at' => null,
        ])->save();

        $this->artisan('bpa:sla-monitor')->assertExitCode(0);

        $task->refresh();

        $this->assertNotNull($task->reminder_sent_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $task->assigned_approver_id,
            'type' => 'sla.reminder',
        ]);

        $reminders = \App\Models\AppNotification::where('type', 'sla.reminder')->count();

        $this->artisan('bpa:sla-monitor')->assertExitCode(0);

        $this->assertSame($reminders, \App\Models\AppNotification::where('type', 'sla.reminder')->count());
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $task = $this->overdueTask();
        $notifications = \App\Models\AppNotification::count();

        $this->artisan('bpa:sla-monitor', ['--dry-run' => true])->assertExitCode(0);

        $this->assertNull($task->refresh()->breached_at);
        $this->assertSame($notifications, \App\Models\AppNotification::count());
        $this->assertSame(0, AuditLog::where('action', 'approval.sla_breached')->count());
    }

    public function test_decided_tasks_are_ignored(): void
    {
        $task = $this->overdueTask();
        $task->forceFill([
            'status' => 'approved',
            'completed_at' => now()->subHour(),
            'due_at' => now()->subDay(),
        ])->save();

        $this->artisan('bpa:sla-monitor')->assertExitCode(0);

        $this->assertNull($task->refresh()->breached_at);
        $this->assertNull($task->reminder_sent_at);
    }
}
