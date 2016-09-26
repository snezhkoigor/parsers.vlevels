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

class Eur extends Base
{
    public $start_index_call = 'EURO FX CALL';
    public $end_index_call = 'EURO FX P (EU)';
    public $start_index_put = 'EURO FX PUT';
    public $end_index_put = '** Option prices quoted per';

    public $new_page_key_call = 'EURO FX CALL';
    public $new_page_key_put = 'EURO FX PUT';

    public $month_end = 'EURO FX FUT';

    public function __construct($option_date = null, $pdf_files_date = null)
    {
        $this->pair = self::PAIR_EUR;

        parent::__construct($option_date, $pdf_files_date);

        $this->pair_with_major = self::PAIR_EUR.self::PAIR_USD;
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

        if (!Schema::hasColumn($this->table_month, '_is_fractal')) {
            $this->createFieldIsFractal();
        }
    }
}