<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:18
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Gbu extends Base
{
    public $json_option_product_id = 8098;
    public $json_pair_name = 'GBU';
    public $json_settle_strike_divide = 1;
    public $calendar_uri = 'https://www.cmegroup.com/trading/fx/g10/british-pound_product_calendar_options.html?optionProductId={option_product_id}#optionProductId={option_product_id}';

    public function __construct($option_date = null, $pdf_files_date = null)
    {
        $this->pair = self::PAIR_GBU;

        parent::__construct($option_date, $pdf_files_date);
        
        $this->pair_with_major = self::PAIR_GBU;
        $this->option = DB::table($this->table)
            ->where(
                [
                    ['_expiration', '>=', $this->option_date],
                    ['_symbol', '=', $this->pair_with_major]
                ]
            )
            ->orderBy('_expiration')
            ->first();

        $this->table_avg = 'cme_avg_' . strtolower($this->pair_with_major);
        $this->table_day = 'cme_day_'.strtolower($this->pair_with_major);
        $this->table_total = 'cme_bill_'.strtolower($this->pair_with_major).'_total';
        $this->table_month = 'cme_bill_'.strtolower($this->pair_with_major).'_'.strtolower($this->option->_option_month);

        $this->json_main_data_link = str_replace(array('{option_product_id}', '{bulletin_date}'), array($this->json_option_product_id, date('Ymd', $this->pdf_files_date)), $this->json_main_data_link);

        $this->cme_file_path = Storage::disk(Base::$storage)->getDriver()->getAdapter()->getPathPrefix() . env('CME_PARSER_JSON_SAVE_FOLDER') . '/' . date('Y', $this->pdf_files_date) . '/' . date('Ymd', $this->pdf_files_date) . '/' . $this->pair . '/';

        if (!Schema::hasColumn($this->table_month, '_is_fractal')) {
            $this->createFieldIsFractal();
        }
    }
}