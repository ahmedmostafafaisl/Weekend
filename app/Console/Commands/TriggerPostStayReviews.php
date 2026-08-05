<?php

namespace App\Console\Commands;

use App\Models\UniteReservation;
use App\Notifications\LeaveReviewNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TriggerPostStayReviews extends Command
{
    protected $signature = 'reviews:trigger';

    protected $description = 'Send a "leave a review" notification to customers whose confirmed reservation date was yesterday, skipping any reservation that has already been rated.';

    public function handle(): int
    {
        $yesterday = now()->subDay()->startOfDay();

        $this->info('['.now()->toDateTimeString()."] Checking confirmed reservations from {$yesterday->toDateString()}…");

        // Confirmed reservations from yesterday, with the customer and this
        // specific reservation's own rating (if any) already loaded so the
        // "already rated" check below doesn't run a query per row.
        $reservations = UniteReservation::query()
            ->where('status', 'confirmed')
            ->whereBetween('reservation_date', [$yesterday->toDateString(), $yesterday->toDateString()])
            ->whereNotNull('user_id')
            ->with(['user', 'unite', 'rating'])
            ->get();

        $sent = 0;
        $skippedNoUser = 0;
        $skippedAlreadyRated = 0;

        foreach ($reservations as $reservation) {
            $user = $reservation->user;
            $unite = $reservation->unite;

            if (! $user || ! $unite) {
                $skippedNoUser++;

                continue;
            }

            // BUG FIX: this used to check $unite->ratings->contains('user_id', ...) —
            // "has this customer EVER rated this venue" — which meant a
            // customer's 2nd, 3rd, etc. stay at the same venue would never
            // get a review prompt at all once they'd rated it the first
            // time. Ratings now tie to a specific reservation
            // (unique('reservation_id')), so each booking is checked
            // independently — rating a previous stay no longer blocks the
            // prompt for a new one.
            $alreadyRated = $reservation->rating !== null;

            if ($alreadyRated) {
                $skippedAlreadyRated++;

                continue;
            }

            $user->notify(new LeaveReviewNotification($reservation));
            $sent++;

            $this->line(sprintf(
                '  → Sent review prompt for reservation #%d (user: %s, venue: %s)',
                $reservation->id,
                $user->email,
                $unite->name
            ));
        }

        $this->info("Done. {$sent} review prompt(s) sent, {$skippedAlreadyRated} skipped (already rated), {$skippedNoUser} skipped (missing user/unite).");

        Log::info('reviews:trigger finished', [
            'date_checked' => $yesterday->toDateString(),
            'sent' => $sent,
            'skipped_already_rated' => $skippedAlreadyRated,
            'skipped_no_user' => $skippedNoUser,
            'ran_at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
