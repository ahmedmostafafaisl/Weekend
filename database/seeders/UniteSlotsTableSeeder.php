<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteSlot;
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
                    // BUG FIX: stadiums are now available 24 hours a day —
                    // this used to seed a limited evening-only window
                    // (16:00/17:00-23:00), which no longer matches the
                    // hourly-only, 24hr booking model. full_start/full_end
                    // are fixed at 00:00/23:59 for every day, matching what
                    // the admin dashboard now sends as hidden inputs (no
                    // manual time entry exists for this type anymore).
                    //
                    // day_start/day_end/buffer_minutes added here for the
                    // availability feature (previously absent — nullable,
                    // so their absence never broke anything, but seed data
                    // never actually exercised the new operating-window
                    // configuration at all). Matches full_start/full_end,
                    // the window already established as this type's
                    // intended operating hours in this seeder.
                    UniteSlot::updateOrCreate(
                        ['unite_id' => $unite->id, 'day_of_week' => $day],
                        [
                            'morning_start' => null, 'morning_end' => null,
                            'evening_start' => null, 'evening_end' => null,
                            'full_start' => '00:00:00',
                            'full_end' => '23:59:00',
                            'status' => 'available',
                            'day_start' => '00:00:00',
                            'day_end' => '23:59:00',
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
        }
    }
}
