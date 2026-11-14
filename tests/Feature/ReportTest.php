<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
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

    public function test_reporting_requires_the_report_permission(): void
    {
        $this->actingAs($this->user('ferdinand@arunika.test'));

        $this->get('/reports')->assertForbidden();
        $this->get('/reports/export?report=requests')->assertForbidden();
    }

    public function test_every_report_tab_renders_for_a_report_viewer(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $expected = [
            'requests' => 'Request summary',
            'approvals' => 'Approval summary',
            'sla' => 'SLA performance',
            'departments' => 'Department activity',
            'statuses' => 'Status distribution',
        ];

        foreach ($expected as $report => $heading) {
            $this->get('/reports?report='.$report)
                ->assertOk()
                ->assertSee($heading);
        }
    }

    public function test_task_reports_switch_the_status_filter_to_task_statuses(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $this->get('/reports?report=requests')->assertSee('Request status');
        $this->get('/reports?report=approvals')->assertSee('Task status');
        $this->get('/reports?report=sla')->assertSee('Task status');
    }

    public function test_the_department_filter_limits_request_rows(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $hr = Department::where('code', 'HR')->firstOrFail();

        $this->get('/reports?report=requests&department='.$hr->id)
            ->assertOk()
            ->assertSee('REQ-202611-0005')
            ->assertDontSee('REQ-202611-0006');
    }

    public function test_an_inverted_date_range_is_rejected(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $this->get('/reports?from=2026-11-14&to=2026-11-01')
            ->assertSessionHasErrors('to');
    }

    public function test_an_empty_period_shows_an_honest_empty_state(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $this->get('/reports?from=2020-01-01&to=2020-01-31')
            ->assertOk()
            ->assertSee('No requests in this period');
    }

    public function test_the_request_export_streams_a_utf8_csv_of_the_filtered_view(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $response = $this->get('/reports/export?report=requests&from=2026-10-15&to=2026-11-14');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=arunika-requests-', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertSame('"Request number",Title,Type,Department,Requester,Status,Priority,Submitted,Completed,"Cycle (business hours)"', $lines[0]);
        $this->assertCount(7, $lines); // header + six seeded requests.
        $this->assertStringContainsString('REQ-202611-0001', $csv);
        $this->assertStringContainsString('REQ-202611-0006', $csv);
    }

    public function test_drafts_are_excluded_from_the_request_export(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        app(RequestService::class)->save($requester, RequestType::where('code', 'LEAVE')->firstOrFail(), [
            'title' => 'A draft that must never appear in reporting',
            'form_data' => [],
        ]);

        $this->actingAs($this->user('budi@arunika.test'));

        $csv = $this->get('/reports/export?report=requests')->streamedContent();

        $this->assertStringNotContainsString('A draft that must never appear in reporting', $csv);
    }

    public function test_csv_cells_that_look_like_formulas_are_neutralised(): void
    {
        $requester = $this->user('ferdinand@arunika.test');

        $request = app(RequestService::class)->save($requester, RequestType::where('code', 'LEAVE')->firstOrFail(), [
            'title' => '=SUM(1,2) leave request',
            'form_data' => [
                'leave_type' => 'Annual leave',
                'start_date' => '2026-12-08',
                'end_date' => '2026-12-12',
                'days' => '5',
                'handover' => 'Maya Sutedja',
            ],
        ]);

        app(RequestService::class)->submit($request, $requester);

        $this->actingAs($this->user('budi@arunika.test'));

        $csv = $this->get('/reports/export?report=requests')->streamedContent();

        $this->assertStringContainsString("\"'=SUM(1,2) leave request\"", $csv);
        $this->assertStringNotContainsString('"=SUM(1,2)', $csv);
    }

    public function test_the_sla_export_reports_numeric_columns(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $csv = $this->get('/reports/export?report=sla')->streamedContent();
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);

        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertSame('Stage,Tasks,"On time",Late,Open,"Compliance %","Average decision (business hours)"', $lines[0]);
        $this->assertGreaterThan(1, count($lines));
        $this->assertStringNotContainsString('—', $csv);
    }

    public function test_the_export_respects_the_department_filter(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $hr = Department::where('code', 'HR')->firstOrFail();

        $csv = $this->get('/reports/export?report=requests&department='.$hr->id)->streamedContent();

        $this->assertStringContainsString('REQ-202611-0005', $csv);
        $this->assertStringNotContainsString('REQ-202611-0006', $csv);
    }

    public function test_an_unknown_report_key_is_rejected(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $this->get('/reports?report=does-not-exist')
            ->assertSessionHasErrors('report');
    }

    public function test_exporting_a_missing_report_key_is_rejected(): void
    {
        $this->actingAs($this->user('budi@arunika.test'));

        $this->get('/reports/export?report=does-not-exist')
            ->assertSessionHasErrors('report');
    }
}
