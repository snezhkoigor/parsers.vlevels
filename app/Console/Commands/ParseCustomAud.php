<?php

namespace App\Console\Commands;

use App\Services\Cme\Aud;
use App\Services\Cme\Base;
use App\Services\Cme\Cad;
use App\Services\Cme\Chf;
use App\Services\Cme\Eur;
use App\Services\Cme\Gbp;
use App\Services\Cme\Jpy;
use App\Services\Cme\Xau;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ParseCustomAud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseCustomAud {date : ex. 2016-01-01}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by custom pair and date';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pdf_files_date = strtotime($this->argument('date'));

        if (!empty($pdf_files_date)) {
            $aud = new Aud(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

            if (($files = $aud->getFiles()) && ($option = $aud->getOption())) {
                $months = $aud->getMonths($aud->getCmeFilePath() . $files[$aud::CME_BULLETIN_TYPE_CALL], $option->_option_month);

                if (count($months) !== 0) {
                    foreach ($months as $month) {
                        $option_by_month = DB::table($this->table)
                            ->where(
                                [
                                    ['_option_month', '=', $month],
                                    ['_symbol', '=', $this->pair_with_major]
                                ]
                            )
                            ->orderBy('_expiration')
                            ->first();

                        if (!empty($option_by_month)) {
                            var_dump($option_by_month->_expiration, $pdf_files_date);die;
                            
                            $other_month = new Aud($option_by_month->_expiration, $pdf_files_date);
var_dump($other_month);die;
                            if ($option->_option_month != $option_by_month->_option_month) {
                                $other_month->update_day_table = false;
                            }

                            $other_month->parse();

                            unset($option_by_month);
                        }
                    }
                }
            }
        }
    }
}
