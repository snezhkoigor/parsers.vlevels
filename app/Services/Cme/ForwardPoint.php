<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:18
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ForwardPoint
{
    public $table = 'cme_forward_points';

    public function parse($file)
    {
        $disk = Storage::disk('public');

        if (!Schema::hasTable($this->table)) {
            $this->createTable();
        }

        $contents = $disk->get($file);

        if ($contents) {
            $lines = explode("\n", $contents);

            if (count($lines) !== 0) {
                foreach ($lines as $line) {
                    if ($line !== '') {
                        $line_arr = explode(',', $line);

                        if (count($line_arr) == 5) {
                            $date = strtotime(date('Y-m-d'));
                            $time = $line_arr[3];
                            
                            $average_of_forward_points = null;
                            $first_forward_point = (float)$line_arr[1];
                            $second_forward_point = (float)$line_arr[2];
                            if (!empty($first_forward_point) || !empty($second_forward_point)) {
                                if (!empty($first_forward_point) && !empty($second_forward_point)) {
                                    $sign = $first_forward_point/abs($first_forward_point);
                                    $average_of_forward_points = $sign * (abs($first_forward_point) + abs($second_forward_point)) / 2;
                                } else if (!empty($first_forward_point)) {
                                    $average_of_forward_points = $first_forward_point;
                                } else {
                                    $average_of_forward_points = $second_forward_point;
                                }
                            }

                            $pair_arr = explode('_', $line_arr[0]);
                            $symbol = null;
                            if (count($pair_arr) == 3) {
                                $symbol = $this->getPairWithMajor($pair_arr[1]);
                            }

                            if (!empty($average_of_forward_points) && !empty($symbol)) {
                                if (($info = $this->get($date, $symbol))) {
                                    $this->edit($info->_id, $average_of_forward_points, $file, $time);
                                } else {
                                    $this->add($date, $symbol, $average_of_forward_points, $file, $time);
                                }
                            }
                        }
                    }
                }
            } else {
                Log::warning('Файл forward point пуст.', [ 'file' => $file ]);
            }
        } else {
            Log::warning('Не смогли получить содержимое файла forward point.', [ 'file' => $file ]);
        }

        return true;
    }

    protected function getPairWithMajor($pair)
    {
        $result = null;

        switch ($pair) {
            case Base::PAIR_AUD:
                $result = Base::PAIR_AUD.Base::PAIR_USD;

                break;

            case Base::PAIR_EUR:
                $result = Base::PAIR_EUR.Base::PAIR_USD;

                break;

            case Base::PAIR_GBP:
                $result = Base::PAIR_GBP.Base::PAIR_USD;

                break;

            case Base::PAIR_CAD:
                $result = Base::PAIR_USD.Base::PAIR_CAD;

                break;

            case Base::PAIR_CHF:
                $result = Base::PAIR_USD.Base::PAIR_CHF;

                break;

            case Base::PAIR_JPY:
                $result = Base::PAIR_USD.Base::PAIR_JPY;

                break;

            case Base::PAIR_XAU:
                $result = Base::PAIR_XAU.Base::PAIR_USD;

                break;
        }
        
        return $result;
    }
    
    protected function get($date, $symbol)
    {
        return DB::table($this->table)->where([ ['_date', '=', $date], ['_symbol', '=', strtoupper($symbol)] ])->first();
    }

    protected function add($date, $symbol, $forward_point, $file_path, $time)
    {
        DB::table($this->table)
            ->insert(
                [
                    '_symbol' => strtoupper($symbol),
                    '_date' => $date,
                    '_forward_point' => $forward_point,
                    '_file_path' => $file_path,
                    '_time' => $time
                ]
            );
    }

    protected function edit($id, $forward_point, $file_path, $time)
    {
        DB::table($this->table)
            ->where('_id', $id)
            ->update(
                [
                    '_forward_point' => $forward_point,
                    '_file_path' => $file_path,
                    '_time' => $time,
                ]
            );
    }

    protected function createTable()
    {
        Log::info('Была создана таблица.', [ 'table' => $this->table ]);

        Schema::create($this->table, function($table) {
            $table->increments('_id');
            $table->integer('_date');
            $table->char('_symbol', 15);
            $table->double('_forward_point');
            $table->mediumText('_file_path');
            $table->char('_time', 15);
        });

        return true;
    }
}