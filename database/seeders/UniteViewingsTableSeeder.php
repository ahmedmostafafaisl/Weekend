<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Unite;
use App\Models\UniteViewing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A handful of actual customer viewing-appointment bookings, one per
 * unite that has at least one active viewing time configured —
 * demonstrating both real outcomes UniteViewingRepository::create()
 * produces: deposit-required (a pending viewing + a real Payment row) and
 * no-deposit (confirmed immediately, no Payment row at all), rather than
 * only ever seeding one of the two paths.
 */
class UniteViewingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $customers = \App\Models\User::where('type', 'customer')->pluck('id');

        if ($customers->isEmpty()) {
            return;
        }

        $unites = Unite::with('viewingTimes')->get();

        foreach ($unites as $i => $unite) {
            $viewingTime = $unite->viewingTimes->firstWhere('status', 'active');

            if (! $viewingTime) {
                continue;
            }

            // Find the next real calendar date that actually falls on this
            // slot's configured day-of-week — a viewing dated for a day
            // the slot doesn't apply to wouldn't be creatable through the
            // real booking flow at all, so seeding one would misrepresent
            // what's actually possible.
            $date = Carbon::today()->addDays(3);
            for ($guard = 0; $guard < 7; $guard++) {
                if (strtolower($date->englishDayOfWeek) === $viewingTime->day_of_week) {
                    break;
                }
                $date->addDay();
            }

            $customerId = $customers[$i % $customers->count()];
            $depositRequired = (bool) $unite->viewing_deposit_enabled;

            $viewing = UniteViewing::updateOrCreate(
                [
                    'unite_id' => $unite->id,
                    'user_id' => $customerId,
                    'unite_viewing_time_id' => $viewingTime->id,
                    'viewing_date' => $date->format('Y-m-d'),
                ],
                [
                    'status' => $depositRequired ? 'pending' : 'confirmed',
                    'deposit_required' => $depositRequired,
                    'deposit_amount' => $depositRequired ? $unite->viewing_deposit_amount : null,
                    'deposit_refundable' => $depositRequired ? $unite->viewing_deposit_refundable : null,
                ]
            );

            if ($depositRequired && ! Payment::where('unite_viewing_id', $viewing->id)->exists()) {
                $payment = Payment::create([
                    'user_id' => $customerId,
                    'unite_viewing_id' => $viewing->id,
                    'payment_type' => 'geidea',
                    'amount' => $unite->viewing_deposit_amount,
                    'reference_id' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                    'payment_id' => 'GD-'.strtoupper(Str::random(12)),
                    'status' => 'pending',
                    'phone' => '96650000000'.($i % 10),
                ]);

                $payment->items()->create([
                    'name' => $unite->name.' — '.__('lang.viewing_deposit'),
                    'item_number' => (string) $viewing->id,
                    'price' => $unite->viewing_deposit_amount,
                    'quantity' => 1,
                    'total_amount' => $unite->viewing_deposit_amount,
                ]);
            }
        }
    }
}
