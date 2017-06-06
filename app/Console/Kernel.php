<?php

namespace App\Console;

use App\Services\Cme\Base;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

use DB;
use Psy\Command\Command;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GetCalendar::class,
        Commands\GetDataFromHTTP::class,
        Commands\GetForwardPointsFromFTP::class,
        Commands\GetFilesFromFTP::class,
        Commands\ParseAud::class,
        Commands\ParseCad::class,
        Commands\ParseChf::class,
        Commands\ParseJpy::class,
        Commands\ParseGbp::class,
        Commands\ParseEur::class,
        Commands\ParseXau::class,
        Commands\ParseSpEom::class,
        Commands\ParseEsEom::class,
        Commands\ParseLo::class,
        Commands\ParseEuu::class,
        Commands\ParseJpu::class,
        Commands\ParseGbu::class,
        Commands\ParseAdu::class,
        Commands\ParseCau::class,
        Commands\ParseChu::class,
        Commands\ParseAvg::class,
        Commands\Demo::class,
        Commands\ParseCustom::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('getDataFromHTTP euu jpu gbu adu cau chu lo xau')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг json файлов остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг json файлов остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyThirtyMinutes()
            ->when(function () {
                return date('G') >= 7 && date('G') <= 13;
            })
            ->withoutOverlapping();

        $schedule->command('getFilesFromFTP')
            ->when(function() {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг PDF файлов остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг PDF файлов остановлен, понедельник.');
                    }

                    $result = false;
                }

                $folder = env('CME_PARSER_SAVE_FOLDER') . '/' . date("Y") . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . date('Ymd', strtotime('-1 DAY', time())) . '/';
                if (Base::isFolderIsNotEmpty($folder)) {
                    Log::info('Папка ' . $folder . ' не пуста. ');
                    $result = false;
                }

                return $result;
            })
            ->everyThirtyMinutes()
            ->when(function () {
                return date('G') >= 7 && date('G') <= 11;
            })
            ->withoutOverlapping();

        $schedule->command('parseAud')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг AUD остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг AUD остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseCad')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг CAD остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг CAD остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseChf')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг CHF остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг CHF остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseEur')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг EUR остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг EUR остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseGbp')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг GBP остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг GBP остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseJpy')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг JPY остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг JPY остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseXau')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг XAU остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг XAU остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseSpEom')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг S&P 500 EOM остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг S&P 500 EOM остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseEsEom')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг E-Mini S&P 500 EOM остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг E-Mini S&P 500 EOM остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseEuu')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe EUR остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe EUR остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseJpu')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe JPY остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe JPY остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseGbu')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe GBP остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe GBP остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseAdu')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe AUD остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe AUD остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseCau')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe CAD остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe CAD остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseLo')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Crude Oil остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Crude Oil остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('parseChu')
            ->when(function () {
                $result = true;

                if (date('w') == 0 || date('w') == 1) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe CHF остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Europe CHF остановлен, понедельник.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('getForwardPointsFromFTP')
            ->when(function () {
                $result = true;

                if (date('w') == 6 || date('w') == 0) {
                    if (date('w') == 0) {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Forward points остановлен, воскресение.');
                    } else {
                        Log::info(date('d.m.Y H:i:s') . '. Парсинг Forward points остановлен, суббота.');
                    }

                    $result = false;
                }

                return $result;
            })
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('parseAvg')
            ->sundays()
            ->withoutOverlapping();

        $schedule->command('GetCalendar xau euu jpu gbu adu cau chu lo')
            ->monthlyOn(1, '23:00')
            ->withoutOverlapping();
    }
}