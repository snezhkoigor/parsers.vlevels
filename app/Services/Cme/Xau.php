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
use Illuminate\Support\Facades\Log;
use SGH\PdfBox\PdfBox;

class Xau extends Base
{
    public $start_index_put = 'OG PUT COMEX GOLD OPTIONS';
    public $end_index_put = 'OG CALL COMEX GOLD OPTIONS';
    public $start_index_call = 'OG CALL COMEX GOLD OPTIONS';
    public $end_index_call = 'SO CALL COMEX SILVER OPTIONS';
    
    public function __construct($date = null)
    {
        $this->pair = self::PAIR_XAU;

        parent::__construct($date);
        
        $this->pair_with_major = self::PAIR_XAU.self::PAIR_USD;
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
        if (!empty($this->option) && is_file($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_CALL]) && is_file($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_PUT])) {
//            $data_call = $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_CALL], $this->option->_option_month, self::CME_BULLETIN_TYPE_CALL);
            $data_put = $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_PUT], $this->option->_option_month, self::CME_BULLETIN_TYPE_PUT);

            $max_oi_call = 0;
            $max_oi_put = 0;
            if (count($data_call) !== 0) {
                $max_oi_call = $this->addCmeData($this->option_date, $data_call, self::CME_BULLETIN_TYPE_CALL);
            } else {
                Log::warning('Не смогли получить основные данные дефолтным методом.', [ 'type' => self::CME_BULLETIN_TYPE_CALL, 'pair' => $this->pair, 'date' => $this->option_date ]);
            }
            if (count($data_put) !== 0) {
                $max_oi_put = $this->addCmeData($this->option_date, $data_put, self::CME_BULLETIN_TYPE_PUT);
            } else {
                Log::warning('Не смогли получить основные данные дефолтным методом.', [ 'type' => self::CME_BULLETIN_TYPE_PUT, 'pair' => $this->pair, 'date' => $this->option_date ]);
            }

            if (DB::table($this->table_month)->where('_date', '=', $this->option_date)->first()) {
                $this->addTotalCmeData($this->option->_id, $this->option_date, $data_call, $data_put);
                $this->updatePairPrints($this->option_date, ($max_oi_call > $max_oi_put ? $max_oi_call : $max_oi_put));
                $this->updateCmeDayTable($this->option_date, $data_call, $data_put, $this->pair_with_major);
                $this->updateCvs($this->option_date, $data_call, $data_put);
            }

            $this->finish($this->option->_id, $this->option_date);
        } else {
            Log::warning('Нет файлов на дату.', [ 'pair' => $this->pair, 'date' => $this->option_date ]);
        }

        return true;
    }
    
    protected function prepareArrayFromPdf($data)
    {
        $strike = null;
        $reciprocal = null;
        $volume = null;
        $oi = null;
        $coi = null;
        $delta = null;
        $cvs = null;
        $cvs_balance = null;
        $print = null;

        if (strpos($data[count($data) - 6], '----') === false) {
            $data[count($data) - 6] = '----'.$data[count($data) - 6];

            if (isset($data[count($data) - 7])) {
                unset($data[count($data) - 7]);
            }

            $data = array_values($data);
        }

        $data[count($data) - 6] = str_replace('----', '', $data[count($data) - 6]);
        if (strlen($data[count($data) - 6]) > 4) {
            $data = array_merge(array_slice($data, 0, 7), array(substr($data[count($data) - 6], (strlen($data[count($data) - 6]) - 4))), array_slice($data, 8));
        }
        
        $reciprocal = $data[3];
        $oi = $data[4];

        // приведем к общему виду
        $data[6] = str_replace('----', '.0000', $data[6]);
        if (strpos($data[6], 'UNCH') !== false) {
            $coi = 0;
            $delta = (float)str_replace('UNCH', '', $data[6]);
        } else {
            $coi_arr = explode('.', $data[6]);

            if (count($coi_arr) == 2) {
                // приведем к общему виду
                $data[5] = str_replace('UNCH', '1', $data[5]);
                
                $coi = ($data[5] / abs($data[5])) * $coi_arr[0];
                $delta = (float)('.'.$coi_arr[1]);
            }
        }
        
        $strike = (int)str_replace('----', '', $data[count($data) - 6]);
        $volume = (int)$data[count($data)-2];

        return array(
            'strike' => $strike,
            'reciprocal' => $reciprocal,
            'volume' => $volume,
            'oi' => $oi,
            'coi' => $coi,
            'delta' => $delta,
            'cvs' => $cvs,
            'cvs_balance' => $cvs_balance,
            'print' => $print
        );
    }
}