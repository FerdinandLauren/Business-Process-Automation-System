<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RequestNumberGenerator
{
    /**
     * Allocate the next request number for the current month, e.g. REQ-202611-0042.
     * The sequence row is incremented inside the caller's transaction; the unique
     * index on requests.request_number is the final guard.
     */
    public function next(?CarbonInterface $at = null): string
    {
        $key = ($at ? Carbon::instance($at) : now())->format('Ym');

        DB::table('request_number_sequences')->insertOrIgnore([
            'month_key' => $key,
            'last_number' => 0,
        ]);

        DB::table('request_number_sequences')
            ->where('month_key', $key)
            ->increment('last_number');

        $sequence = (int) DB::table('request_number_sequences')
            ->where('month_key', $key)
            ->value('last_number');

        return sprintf('REQ-%s-%04d', $key, $sequence);
    }
}
