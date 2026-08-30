<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteSlot;
use App\Models\UniteSlotPeriod;
use Illuminate\Database\Seeder;

class UniteSlotsTableSeeder extends Seeder
{
    public function run(): void
    {
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach (Unite::all() as $unite) {
            foreach ($days as $day) {
                $isClosed = ($day === 'sunday' && $unite->type === 'hall'); // halls closed Sundays

                if ($unite->type === 'stadium') {
                    // BUG FIX (earlier): stadiums are hourly-only, so this
                    // used to seed a limited evening-only window
                    // (16:00/17:00-23:00), which didn't match the actual
                    // hourly booking model at all.
                    //
                    // UPDATE (this session): now genuinely overnight (08:00
                    // to 02:00 the following morning) instead of a same-day
                    // 24hr window (00:00-23:59) -- demonstrates the
                    // overnight-booking feature directly in seed data,
                    // rather than a window that never actually exercised
                    // it. Chosen to still fully contain every existing
                    // reservation example below (10:00-12:00, 19:00-21:00,
                    // 17:00-20:00), while also making room for the new
                    // overnight example (23:00-01:00) added alongside it.
                    //
                    // day_start/day_end/buffer_minutes added for the
                    // availability feature, matching full_start/full_end,
                    // this type's intended operating hours in this seeder.
                    UniteSlot::updateOrCreate(
                        ['unite_id' => $unite->id, 'day_of_week' => $day],
                        [
                            'morning_start' => null, 'morning_end' => null,
                            'evening_start' => null, 'evening_end' => null,
                            'full_start' => '08:00:00',
                            'full_end' => '02:00:00',
                            'status' => 'available',
                            'day_start' => '08:00:00',
                            'day_end' => '02:00:00',
                            'buffer_minutes' => 15,
                        ]
                    );
                } elseif ($unite->type === 'hall') {
                    // BUG FIX: halls are now full-day only — morning_start/
                    // morning_end/evening_start/evening_end must genuinely
                    // be null (they're no longer accepted at all via the
                    // create/update form), not populated with times nobody
                    // can set through the app anymore. full_start/full_end
                    // still represent the hall's real operating window.
                    UniteSlot::updateOrCreate(
                        ['unite_id' => $unite->id, 'day_of_week' => $day],
                        [
                            'morning_start' => null, 'morning_end' => null,
                            'evening_start' => null, 'evening_end' => null,
                            'full_start' => '08:00:00', 'full_end' => '23:30:00',
                            'status' => $isClosed ? 'unavailable' : 'available',
                            'day_start' => '08:00:00',
                            'day_end' => '23:30:00',
                            'buffer_minutes' => 15,
                        ]
                    );
                } elseif ($unite->type === 'lounge') {
                    UniteSlot::updateOrCreate(
                        ['unite_id' => $unite->id, 'day_of_week' => $day],
                        [
                            'morning_start' => '09:00:00', 'morning_end' => '14:00:00',
                            'evening_start' => '17:00:00', 'evening_end' => '23:00:00',
                            'full_start' => '09:00:00', 'full_end' => '23:00:00',
                            'status' => 'available',
                            'day_start' => '08:00:00',
                            'day_end' => '23:00:00',
                            'buffer_minutes' => 15,
                        ]
                    );
                } else { // camp
                    UniteSlot::updateOrCreate(
                        ['unite_id' => $unite->id, 'day_of_week' => $day],
                        [
                            'morning_start' => '07:00:00', 'morning_end' => '13:00:00',
                            'evening_start' => '15:00:00', 'evening_end' => '22:00:00',
                            'full_start' => '07:00:00', 'full_end' => '22:00:00',
                            'status' => 'available',
                            'day_start' => '07:00:00',
                            'day_end' => '23:00:00',
                            'buffer_minutes' => 15,
                        ]
                    );
                }
            }

            // Custom periods (UniteSlotPeriod) -- new this session, no seed
            // data existed for this at all before. Seeded on 'friday' only
            // per unite (illustrative examples, not a full weekly
            // schedule) to keep this seeder's output reviewable.
            if ($unite->type === 'stadium') {
                $fridaySlot = UniteSlot::where('unite_id', $unite->id)->where('day_of_week', 'friday')->first();
                if ($fridaySlot) {
                    // Genuinely overnight (23:00 to 01:00 the following
                    // morning), within the stadium's own 08:00-02:00
                    // operating window -- a "prime night" premium slot.
                    UniteSlotPeriod::updateOrCreate(
                        ['unite_slot_id' => $fridaySlot->id, 'start_time' => '23:00:00', 'end_time' => '01:00:00'],
                        ['status' => 'available']
                    );
                }
            } elseif ($unite->type === 'lounge') {
                $fridaySlot = UniteSlot::where('unite_id', $unite->id)->where('day_of_week', 'friday')->first();
                if ($fridaySlot) {
                    // Same-day example (14:00-16:00) -- confirms custom
                    // periods work identically for a normal, non-overnight
                    // case, not just the stadium's overnight one above.
                    UniteSlotPeriod::updateOrCreate(
                        ['unite_slot_id' => $fridaySlot->id, 'start_time' => '14:00:00', 'end_time' => '16:00:00'],
                        ['status' => 'available']
                    );
                }
            }
        }
    }
}
