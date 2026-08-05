<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run every day at midnight — mark expired subscriptions as inactive
        $schedule->command('subscriptions:expire')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/subscriptions-expire.log'));

        // Run every day shortly after midnight — prompt customers to review
        // venues from confirmed reservations that took place yesterday.
        // Offset by 5 minutes from subscriptions:expire so the two don't
        // contend for the same run-in-background slot at exactly 00:00.
        $schedule->command('reviews:trigger')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/reviews-trigger.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
