<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:18
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Eur extends Base
{
    public function __construct($date = null)
    {
        $this->pair = self::PAIR_EUR;

        parent::__construct($date);
        
        $this->pair_with_major = self::PAIR_EUR.self::PAIR_USD;
        $this->option = DB::table($this->table)
            ->where(
                [
                    ['_expiration', '>', $this->option_date],
                    ['_symbol', '=', $this->pair_with_major]
                ]
            )
            ->orderBy('_expiration')
            ->first();
        
        $this->table_day = 'cme_day_'.strtolower($this->pair_with_major);
        $this->table_total = 'cme_bill_'.strtolower($this->pair_with_major).'_total';
        $this->table_month = 'cme_bill_'.strtolower($this->pair_with_major).'_'.strtolower($this->option->_option_month);
        $this->cme_file_path = Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix() . env('CME_PARSER_SAVE_FOLDER') . '/' . date('Y', $this->option_date) . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . date('Ymd', $this->option_date) . '/'; 
    }

    public function parse()
    {
        if (!empty($this->option)) {
            $data_call = $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_CALL], $this->option->_option_month, self::CME_BULLETIN_TYPE_CALL);
            $data_put = $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_PUT], $this->option->_option_month, self::CME_BULLETIN_TYPE_PUT);

            $max_oi_call = 0;
            $max_oi_put = 0;
            if (count($data_call) !== 0) {
                $max_oi_call = $this->addCmeData($this->option_date, $data_call, self::CME_BULLETIN_TYPE_CALL);
            }
            if (count($data_put) !== 0) {
                $max_oi_put = $this->addCmeData($this->option_date, $data_put, self::CME_BULLETIN_TYPE_PUT);
            }

            if (DB::table($this->table_month)->where('_date', '=', $this->option_date)->first()) {
                $this->addTotalCmeData($this->option->_id, $this->option_date, $data_call, $data_put);
                $this->updatePairPrints($this->option_date, ($max_oi_call > $max_oi_put ? $max_oi_call : $max_oi_put));
                $this->updateCmeDayTable($this->option_date, $data_call, $data_put, $this->pair_with_major);
            }

            $this->finish($this->option->_id, $this->option_date);
        }

        return true;
    }

    private function getRows($file, $month, $type)
    {
        $flag = false;
        $text = array();
        $out = array();

        $content = $this->extract($file);

        if ($content) {
            $key_prefix = ($type == self::CME_BULLETIN_TYPE_CALL) ? 'EURO FX CALL' : 'EURO FX PUT';

            foreach ($content as $c) {
                if ($flag) {
                    $flag = false;
                    $c = array_slice($c, 19);   //Убираем заголовок новой старницы
                    $c = array_merge($text, $c);
                }

                $keys = array_keys( $c, $key_prefix );
                foreach ($keys as $key ) {
                    if ($c[$key+1] == $month or $c[$key+2] == $month) {
                        //Обрезаем найденный массив
                        if ($c[$key+1]==$month) {
                            $slice = 2;
                        } else {
                            $slice = 3;
                        }

                        $data = array_slice($c, $key);
                        $key_2 = array_search('TOTAL', $data);
                        //Условие, если данные перешли на другую страницу
                        if ($key_2) {
                            $data = array_slice($data, $slice);
                            $data = array_slice($data, 0, $key_2);
                            $data = array_chunk($data, 14);

                            foreach ($data as $d) {
                                $out[] = $this->prepareArrayFromPdf($d);
                            }
                            //Если начало на одной странице, а продолжение на другой
                        } else {
                            $flag = true;
                            $text = array_slice($data, 0, count($data)-3);
                        }
                    }
                }
            }
        }

        return $this->clearEmptyStrikeValues($out);
    }
}