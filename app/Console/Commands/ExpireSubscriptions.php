<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark active subscriptions as inactive when end_date has passed or count has reached zero.';

    public function handle(): int
    {
        $this->info('['.now()->toDateTimeString().'] Checking for expired subscriptions…');

        // Load all active subscriptions that might be expired
        $candidates = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->where(fn ($q2) => $q2->whereNotNull('end_date')->whereDate('end_date', '<', now()))
                    ->orWhere(fn ($q2) => $q2->whereNotNull('count')->where('count', '<=', 0));
            })
            ->with(['adPackage', 'propertyPackage', 'user'])
            ->get();

        $expired = 0;

        foreach ($candidates as $sub) {
            $reason = $this->expiredReason($sub);

            $sub->update(['status' => 'inactive']);
            $expired++;

            $this->line(sprintf(
                '  → Expired subscription #%d (user: %s, type: %s, reason: %s)',
                $sub->id,
                $sub->user?->email ?? 'unknown',
                $sub->type,
                $reason
            ));

            Log::info('Subscription expired', [
                'subscription_id' => $sub->id,
                'user_id' => $sub->user_id,
                'type' => $sub->type,
                'reason' => $reason,
                'end_date' => $sub->end_date?->toDateString(),
                'count' => $sub->count,
            ]);
        }

        $this->info("Done. {$expired} subscription(s) marked as inactive.");

        Log::info('subscriptions:expire finished', [
            'expired' => $expired,
            'ran_at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }

    private function expiredReason(Subscription $sub): string
    {
        $reasons = [];

        if ($sub->end_date && $sub->end_date->lt(now()->startOfDay())) {
            $reasons[] = 'end_date passed ('.$sub->end_date->toDateString().')';
        }

        if (! is_null($sub->count) && $sub->count <= 0) {
            $reasons[] = 'count reached zero';
        }

        return implode(' + ', $reasons) ?: 'unknown';
    }
}
