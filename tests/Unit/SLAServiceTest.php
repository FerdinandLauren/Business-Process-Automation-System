<?php

namespace Tests\Unit;

use App\Services\SLAService;
use Carbon\Carbon;
use Tests\TestCase;

class SLAServiceTest extends TestCase
{
    private SLAService $sla;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sla = new SLAService;
    }

    public function test_it_adds_hours_inside_one_working_day(): void
    {
        // Wednesday 4 November 2026, 10:00 + 4 business hours.
        $start = Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta');

        $due = $this->sla->addBusinessHours($start, 4);

        $this->assertSame('2026-11-04 14:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function test_it_skips_the_weekend_when_adding_hours(): void
    {
        // Friday 16:00 + 4h = 1h before the weekend, then 3h on Monday.
        $start = Carbon::parse('2026-11-06 16:00:00', 'Asia/Jakarta');

        $due = $this->sla->addBusinessHours($start, 4);

        $this->assertSame('2026-11-09 11:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function test_it_moves_a_weekend_start_to_the_next_working_morning(): void
    {
        $start = Carbon::parse('2026-11-07 10:00:00', 'Asia/Jakarta');

        $due = $this->sla->addBusinessHours($start, 2);

        $this->assertSame('2026-11-09 10:00:00', $due->format('Y-m-d H:i:s'));
    }

    public function test_it_carries_remaining_minutes_into_the_next_day(): void
    {
        // Wednesday 16:30 + 2h = 30m on Wednesday, 90m on Thursday.
        $start = Carbon::parse('2026-11-04 16:30:00', 'Asia/Jakarta');

        $due = $this->sla->addBusinessHours($start, 2);

        $this->assertSame('2026-11-05 09:30:00', $due->format('Y-m-d H:i:s'));
    }

    public function test_zero_hours_returns_the_start_unchanged(): void
    {
        $start = Carbon::parse('2026-11-07 10:00:00', 'Asia/Jakarta');

        $this->assertTrue($this->sla->addBusinessHours($start, 0)->equalTo($start));
    }

    public function test_business_minutes_ignore_time_outside_working_hours(): void
    {
        $start = Carbon::parse('2026-11-06 16:00:00', 'Asia/Jakarta');
        $end = Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta');

        // Friday 16:00-17:00 (60) + Monday 08:00-10:00 (120).
        $this->assertSame(180, $this->sla->businessMinutesBetween($start, $end));
    }

    public function test_business_minutes_inside_one_day(): void
    {
        $start = Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta');
        $end = Carbon::parse('2026-11-04 12:30:00', 'Asia/Jakarta');

        $this->assertSame(150, $this->sla->businessMinutesBetween($start, $end));
    }

    public function test_business_minutes_are_zero_across_a_weekend(): void
    {
        $start = Carbon::parse('2026-11-07 09:00:00', 'Asia/Jakarta');
        $end = Carbon::parse('2026-11-08 20:00:00', 'Asia/Jakarta');

        $this->assertSame(0, $this->sla->businessMinutesBetween($start, $end));
    }

    public function test_business_minutes_are_never_negative(): void
    {
        $start = Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta');
        $end = Carbon::parse('2026-11-09 09:00:00', 'Asia/Jakarta');

        $this->assertSame(0, $this->sla->businessMinutesBetween($start, $end));
    }

    public function test_it_classifies_sla_states(): void
    {
        $now = Carbon::parse('2026-11-14 10:00:00', 'Asia/Jakarta');

        $this->assertSame('none', $this->sla->state(null, $now));
        $this->assertSame('overdue', $this->sla->state($now->copy()->subMinutes(5), $now));
        $this->assertSame('due_soon', $this->sla->state($now->copy()->addHours(2), $now));
        $this->assertSame('due_soon', $this->sla->state($now->copy()->addHours(4), $now));
        $this->assertSame('on_track', $this->sla->state($now->copy()->addHours(5), $now));
        $this->assertSame('on_track', $this->sla->state($now->copy()->addDays(3), $now));
    }

    public function test_it_humanises_the_remaining_time(): void
    {
        $now = Carbon::parse('2026-11-14 10:00:00', 'Asia/Jakarta');

        $this->assertSame('No SLA', $this->sla->humanizeRemaining(null, $now));
        $this->assertSame('1h 30m left', $this->sla->humanizeRemaining($now->copy()->addMinutes(90), $now));
        $this->assertSame('45m overdue', $this->sla->humanizeRemaining($now->copy()->subMinutes(45), $now));
        $this->assertSame('2d 3h left', $this->sla->humanizeRemaining($now->copy()->addHours(51), $now));
    }
}
