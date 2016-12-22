<?php
/**
 * Created by PhpStorm.
 * User: igorsnezko
 * Date: 07.12.16
 * Time: 11:55
 */

namespace App\Services\Cme;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Cl extends Base
{
    public $start_index_call = 'LO CALL NYMEX CRUDE OIL OPTIONS (PHY)';
    public $end_index_call = 'LO PUT NYMEX CRUDE OIL OPTIONS (PHY)';
    public $start_index_put = 'LO PUT NYMEX CRUDE OIL OPTIONS (PHY)';
    public $end_index_put = 'OH CALL';

    public $new_page_key_call = 'LO CALL';
    public $new_page_key_put = 'LO PUT';

    public $month_end = 'EOM S&P 500 OPT';

    public $json_option_product_id = 190;
    public $json_pair_name = 'LO';

    public function __construct($option_date = null, $pdf_files_date = null)
    {
        $this->pair = self::PAIR_CL;

        parent::__construct($option_date, $pdf_files_date);

        $this->pair_with_major = self::PAIR_CL;
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

        $this->json_main_data_link = str_replace(array('{option_product_id}', '{bulletin_date}'), array($this->json_option_product_id, date('Ymd', $this->pdf_files_date)), $this->json_main_data_link);

        switch (config('app.parser')) {
            case Base::PARSER_TYPE_PDF:
                $this->cme_file_path = Storage::disk(Base::$storage)->getDriver()->getAdapter()->getPathPrefix() . env('CME_PARSER_SAVE_FOLDER') . '/' . date('Y', $this->pdf_files_date) . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . date('Ymd', $this->pdf_files_date) . '/';

                break;

            case Base::PARSER_TYPE_JSON:
                $this->cme_file_path = Storage::disk(Base::$storage)->getDriver()->getAdapter()->getPathPrefix() . env('CME_PARSER_JSON_SAVE_FOLDER') . '/' . date('Y', $this->pdf_files_date) . '/' . date('Ymd', $this->pdf_files_date) . '/' . $this->pair . '/';

                break;
        }

        if (!Schema::hasColumn($this->table_month, '_is_fractal')) {
            $this->createFieldIsFractal();
        }
    }
}