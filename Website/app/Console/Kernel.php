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
        // Cleanup old guest jobs and quizzes (older than 24 hours) to prevent DB bloat
        $schedule->call(function () {
            \App\Models\ToolJob::whereNull('user_id')
                ->where('created_at', '<', now()->subDays(1))
                ->delete();
                
            \App\Models\Quiz::whereNull('user_id')
                ->where('created_at', '<', now()->subDays(1))
                ->delete();
        })->daily()->name('cleanup-guest-data');
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
