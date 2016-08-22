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
        Commands\GetFilesFromFTP::class,
        Commands\ParseAud::class,
        Commands\ParseCad::class,
        Commands\ParseChf::class,
        Commands\ParseJpy::class,
        Commands\ParseGbp::class,
        Commands\ParseEur::class,
        Commands\ParseXau::class
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
                    Log::warning('Папка ' . env('CME_PARSER_SAVE_FOLDER') . '/' . date("Y") . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . '/' . ' не пуста. ');
                    die;
                }
            })
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::info('Файлы успешно скопированы в папку ' . env('CME_PARSER_SAVE_FOLDER') . '/' . date("Y") . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . '/');
            });

        $schedule->command('ParseAud')
            ->before(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Начался парсинг AUD');
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг AUD');
            });

        $schedule->command('ParseCad')
            ->before(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Начался парсинг CAD');
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг CAD');
            });

        $schedule->command('ParseChf')
            ->before(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Начался парсинг CHF');
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг CHF');
            });

        $schedule->command('ParseEur')
            ->before(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Начался парсинг EUR');
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг EUR');
            });

        $schedule->command('ParseGbp')
            ->before(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Начался парсинг Gbp');
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг Gbp');
            });

        $schedule->command('ParseJpy')
            ->before(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Начался парсинг JPY');
            })
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->after(function () {
                Log::warning(date('d.m.Y H:i:s') . '. Завершился парсинг JPY');
            });
    }
}