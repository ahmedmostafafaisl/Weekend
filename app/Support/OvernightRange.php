<?php

namespace App\Support;

use Carbon\Carbon;

class OvernightRange
{
    /**
     * Returns $end, bumped one calendar day forward if it isn't strictly
     * after $start -- the actual signal that a [start, end] time range
     * wraps past midnight (e.g. start=22:00, end=02:00 the next day).
     * Does not mutate $start or $end; returns a fresh Carbon instance.
     */
    public static function normalizeEnd(Carbon $start, Carbon $end): Carbon
    {
        $end = $end->copy();

        if ($end->lte($start)) {
            $end->addDay();
        }

        return $end;
    }
}
