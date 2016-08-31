<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GetForwardPointsFromFTP::class,
        Commands\GetFilesFromFTP::class,
        Commands\ParseAud::class,
        Commands\ParseCad::class,
        Commands\ParseChf::class,
        Commands\ParseJpy::class,
        Commands\ParseGbp::class,
        Commands\ParseEur::class,
        Commands\ParseXau::class,
        Commands\Demo::class
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
            ->when(function() {
                $result = true;

                if (count(Storage::disk('public')->files(env('CME_PARSER_SAVE_FOLDER') . '/' . date("Y") . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . '/')) != 0) {
                    Log::warning('Папка ' . env('CME_PARSER_SAVE_FOLDER') . '/' . date("Y") . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . '/' . ' не пуста. ');
                    $result = false;
                }

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг PDF файлов.');
            });

        $schedule->command('parseAud')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг AUD.');
            });

        $schedule->command('parseCad')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг CAD.');
            });

        $schedule->command('parseChf')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг CHF.');
            });

        $schedule->command('parseEur')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг EUR.');
            });

        $schedule->command('parseGbp')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг GBP.');
            });

        $schedule->command('parseJpy')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг JPY.');
            });

        $schedule->command('parseXau')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг XAU.');
            });

        $schedule->command('getForwardPointsFromFTP')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 6) {
                    $result = false;
                }

                return $result;
            })
            ->hourly()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг Forward points.');
            });
    }
}