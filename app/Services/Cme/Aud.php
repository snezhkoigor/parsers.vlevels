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
use Illuminate\Support\Facades\Storage;

class Aud extends Base
{
    public $start_index_call = 'AUST DLR CALL';
    public $end_index_call = '** Option prices quoted per Daily Information';
    public $start_index_put = 'AUST DLR PUT';
    public $end_index_put = '** Option prices quoted per Daily Information';

    public $new_page_key_call = 'AUST DLR CALL (';
    public $new_page_key_put = 'AUST DLR PUT (';

    public $month_end = 'AUST DLR FUT';

    public function __construct($option_date = null, $pdf_files_date = null)
    {
        $this->pair = self::PAIR_AUD;

        parent::__construct($option_date, $pdf_files_date);

        $this->pair_with_major = self::PAIR_AUD.self::PAIR_USD;
        $this->option = DB::table($this->table)
            ->where(
                [
                    ['_expiration', '>=', $this->option_date],
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
}