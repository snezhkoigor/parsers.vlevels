<?php

namespace App\Console;

use App\Services\Cme\Base;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

use DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
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
        $schedule->command('getDataFromHTTP aud cad eur jpy gbp chf xau')
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
                return date('G') >= 7 && date('G') <= 11;
            })
            ->withoutOverlapping()
            ->after(function () {
                Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг json файлов.');
            });

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
            ->withoutOverlapping()
            ->after(function () {
                Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг PDF файлов.');
            });

        if (env('CME_PARSER_USE') == Base::PARSER_TYPE_PDF) {
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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг AUD.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг CAD.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг CHF.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг EUR.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг GBP.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг JPY.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг XAU.');
                });

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
                ->withoutOverlapping()
                ->after(function () {
                    Log::info(date('d.m.Y H:i:s') . '. Завершился парсинг Forward points.');
                });
        } elseif (env('CME_PARSER_USE') == Base::PARSER_TYPE_JSON) {
            
        }
    }
}