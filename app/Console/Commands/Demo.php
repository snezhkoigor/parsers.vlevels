<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Demo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo Scheduler';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        $tables = DB::connection('production')->select('SHOW TABLES');
//
//        if (count($tables) !== 0) {
//            foreach ($tables as $table) {
//                $name = $table->Tables_in_cme;
//
//                if (strpos($name, 'cme_bill_') !== false && strpos($name, '_total') === false) {
//                    $data = DB::connection('production')->select('SELECT * FROM ' . $name);
//
//                    if (count($data) !== 0) {
//                        $arr = [];
//                        foreach ($data as $item) {
//                            $arr[$item->_type][$item->_date][] = [
//                                '_id' => $item->_id,
//                                '_type' => $item->_type,
//                                '_volume' => $item->_volume,
//                                '_date' => $item->_date,
//                                '_strike' => $item->_strike
//                            ];
//                        }
//
//                        if (count($arr) !== 0) {
//                            $min_fractal_volume = strpos($name, 'usdchf') !== false ? 1 : 10;
//
//                            foreach ($arr as $type => $dates) {
//                                foreach ($dates as $key => $date) {
//                                    for ($i = 1; $i < (count($date) - 2); $i++) {
//                                        if ($date[$i]['_volume'] > $date[$i - 1]['_volume'] &&
//                                            $date[$i]['_volume'] > $date[$i + 1]['_volume'] &&
//                                            $date[$i - 1]['_volume'] >= $min_fractal_volume &&
//                                            $date[$i + 1]['_volume'] >= $min_fractal_volume
//                                        ) {
//                                            DB::connection('production')->table($name)
//                                                ->where(
//                                                    [
//                                                        ['_strike', '=', $date[$i]['_strike']],
//                                                        ['_date', '=', $date[$i]['_date']],
//                                                        ['_type', '=', $date[$i]['_type']],
//                                                        ['_id', '=', $date[$i]['_id']],
//                                                    ]
//                                                )
//                                                ->update(['_is_fractal' => 1]);
//                                        }
//                                    }
//                                }
//                            }
//                        }
//
//                        echo 'update ' . $name . "\n";
//                    }
//                }
//            }
//        }
    }
}
