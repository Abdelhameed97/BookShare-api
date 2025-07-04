<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\BookAiSearchTest::class,
        \App\Console\Commands\BookAiIndexBuild::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // ...existing code...
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
