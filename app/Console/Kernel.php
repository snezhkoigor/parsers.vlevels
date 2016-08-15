<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Storage;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GetFilesFromFTP::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('getFilesFromFTP')
            ->before(function () {
                if (count(Storage::disk('public')->files(env('CME_PARSER_SAVE_FOLDER') . '/' . date("Y") . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . '/')) != 0) {
                    echo 'Директория не пустая.';
                    die;
                }
            })
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->after(function () {
                echo 'Завершено';
            });

    }
}