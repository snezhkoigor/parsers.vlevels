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

class Xau extends Base
{
    public $start_index_put = 'OG PUT COMEX GOLD OPTIONS';
    public $end_index_put = 'OG CALL COMEX GOLD OPTIONS';
    public $start_index_call = 'OG CALL COMEX GOLD OPTIONS';
    public $end_index_call = 'SO CALL COMEX SILVER OPTIONS';

    public $new_page_key_call = 'OG CALL COMEX GOLD OPTIONS';
    public $new_page_key_put = 'OG PUT COMEX GOLD OPTIONS';

    public $month_end = 'HX CALL';

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
        $this->cme_file_path = Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix() . env('CME_PARSER_SAVE_FOLDER') . '/' . date('Y', $this->pdf_files_date) . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . date('Ymd', $this->pdf_files_date) . '/';
    }

    protected function prepareItemFromParse($key, $data)
    {
        $result = array();

        switch ($key) {
            case 0:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 6) {
                    $result = $data_arr;
                } else {
                    $result = array_merge($data_arr, array('+'));
                }

                break;

            case 1:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 2) {
                    $result = $data_arr;
                } else {

                }

                break;

            case 2:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 2) {
                    $result = $data_arr;
                }

                break;

            case 3:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 6) {
                    $result = $data_arr;
                }

                break;
        }

        return $result;
    }
    
    protected function prepareArrayFromPdf($data)
    {
        $strike = (int)$data[10];
        $oi = (int)$data[4];
        $coi = (($data[5] == '+') ? 1 : -1 )*(int)$data[7];
        $delta = (float)$data[8];
        $reciprocal = (float)$data[3];
        $volume = (int)$data[14];

        return array(
            'strike' => $strike,
            'reciprocal' => $reciprocal,
            'volume' => $volume,
            'oi' => $oi,
            'coi' => $coi,
            'delta' => $delta,
            'cvs' => null,
            'cvs_balance' => null,
            'print' => null
        );
    }
}