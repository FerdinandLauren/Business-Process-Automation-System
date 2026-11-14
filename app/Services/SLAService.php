<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class SLAService
{
    /**
     * Add business hours to a moment, skipping weekends and non-working hours.
     */
    public function addBusinessHours(CarbonInterface $start, int $hours): Carbon
    {
        $cursor = Carbon::instance($start)->setTimezone(config('app.timezone'));
        $remaining = max($hours, 0) * 60;

        if ($remaining === 0) {
            return $cursor;
        }

        $cursor = $this->nextWorkingMoment($cursor);
        $guard = 0;

        while ($remaining > 0 && $guard++ < 3650) {
            $dayEnd = $cursor->copy()->setTimeFromTimeString($this->endOfDay());
            $available = (int) $cursor->diffInMinutes($dayEnd, false);

            if ($available <= 0) {
                $cursor = $this->nextWorkingMoment($cursor->copy()->addDay()->startOfDay());

                continue;
            }

            $take = min($remaining, $available);
            $cursor->addMinutes((int) $take);
            $remaining -= $take;

            if ($remaining > 0) {
                $cursor = $this->nextWorkingMoment($cursor->copy()->addDay()->startOfDay());
            }
        }

        return $cursor;
    }

    /**
     * Count business minutes between two moments (used by reporting).
     */
    public function businessMinutesBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $cursor = $this->nextWorkingMoment(Carbon::instance($start)->setTimezone(config('app.timezone')));
        $limit = Carbon::instance($end)->setTimezone(config('app.timezone'));
        $minutes = 0;
        $guard = 0;

        while ($cursor->lessThan($limit) && $guard++ < 3650) {
            $dayEnd = $cursor->copy()->setTimeFromTimeString($this->endOfDay());
            $windowEnd = $dayEnd->lessThan($limit) ? $dayEnd : $limit->copy();
            $available = (int) $cursor->diffInMinutes($windowEnd, false);

            if ($available > 0) {
                $minutes += $available;
            }

            $cursor = $this->nextWorkingMoment($cursor->copy()->addDay()->startOfDay());
        }

        return $minutes;
    }

    /**
     * @return 'overdue'|'due_soon'|'on_track'|'none'
     */
    public function state(?CarbonInterface $dueAt, ?CarbonInterface $now = null): string
    {
        if ($dueAt === null) {
            return 'none';
        }

        $now = $now ? Carbon::instance($now) : now();

        if ($dueAt->lessThan($now)) {
            return 'overdue';
        }

        $threshold = (int) config('bpa.sla.reminder_hours_before', 4) * 60;
        $minutesUntilDue = (int) $now->diffInMinutes($dueAt, false);

        return $minutesUntilDue <= $threshold ? 'due_soon' : 'on_track';
    }

    public function humanizeRemaining(?CarbonInterface $dueAt, ?CarbonInterface $now = null): string
    {
        if ($dueAt === null) {
            return 'No SLA';
        }

        $now = $now ? Carbon::instance($now) : now();
        $minutes = (int) $now->diffInMinutes($dueAt, false);

        if ($minutes < 0) {
            return $this->formatMinutes(abs($minutes)).' overdue';
        }

        return $this->formatMinutes($minutes).' left';
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours >= 24) {
            $days = intdiv($hours, 24);
            $hours %= 24;

            return trim($days.'d '.($hours > 0 ? $hours.'h' : ''));
        }

        return trim($hours.'h '.($rest > 0 ? $rest.'m' : ''));
    }

    public function nextWorkingMoment(CarbonInterface $moment): Carbon
    {
        $cursor = Carbon::instance($moment)->setTimezone(config('app.timezone'));
        $guard = 0;

        while ($guard++ < 400) {
            if (! $this->isWorkday($cursor)) {
                $cursor = $cursor->copy()->addDay()->startOfDay();

                continue;
            }

            $dayStart = $cursor->copy()->setTimeFromTimeString($this->startOfDay());
            $dayEnd = $cursor->copy()->setTimeFromTimeString($this->endOfDay());

            if ($cursor->lessThan($dayStart)) {
                return $dayStart;
            }

            if ($cursor->greaterThanOrEqualTo($dayEnd)) {
                $cursor = $cursor->copy()->addDay()->startOfDay();

                continue;
            }

            return $cursor;
        }

        return $cursor;
    }

    public function isWorkday(CarbonInterface $moment): bool
    {
        return in_array((int) $moment->dayOfWeekIso, config('bpa.sla.workdays', [1, 2, 3, 4, 5]), true);
    }

    public function startOfDay(): string
    {
        return config('bpa.sla.workday_start', '08:00');
    }

    public function endOfDay(): string
    {
        return config('bpa.sla.workday_end', '17:00');
    }
}
