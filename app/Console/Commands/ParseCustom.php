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

class ParseCustom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseCustom {instrument : without USD (ex: aud)} {date_from : ex. 2016-01-01}  {date_to : ex. 2016-01-01}';

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
        $instrument = strtoupper($this->argument('instrument'));
        $pdf_files_date_from = strtotime($this->argument('date_from'));
        $pdf_files_date_to = strtotime($this->argument('date_to'));

        $pair_obj = null;
        if (!empty($instrument) && !empty($pdf_files_date_from) && !empty($pdf_files_date_to)) {
            while ($pdf_files_date_from <= $pdf_files_date_to) {
                $pdf_files_date = $pdf_files_date_from;

                echo 'Парсим ' . date('Y-m-d', $pdf_files_date) . '.';

                $pair_obj = null;
                switch ($instrument) {
                    case Base::PAIR_AUD:
                        $pair_obj = new Aud(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;

                    case Base::PAIR_CAD:
                        $pair_obj = new Cad(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;

                    case Base::PAIR_CHF:
                        $pair_obj = new Chf(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;

                    case Base::PAIR_EUR:
                        $pair_obj = new Eur(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;

                    case Base::PAIR_GBP:
                        $pair_obj = new Gbp(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;

                    case Base::PAIR_JPY:
                        $pair_obj = new Jpy(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;

                    case Base::PAIR_XAU:
                        $pair_obj = new Xau(strtotime('+1 DAY', $pdf_files_date), $pdf_files_date);

                        break;
                }

                switch (config('app.parser')) {
                    case Base::PARSER_TYPE_PDF:
                        if ($pair_obj && ($files = $pair_obj->getFiles()) && ($option = $pair_obj->getOption())) {
                            $months = $pair_obj->getMonths($pair_obj->getCmeFilePath() . $files[$pair_obj::CME_BULLETIN_TYPE_CALL], $option->_option_month);

                            if (count($months) !== 0) {
                                foreach ($months as $month) {
                                    $option_by_month = $pair_obj->getOptionDataByMonth($month);

                                    if (!empty($option_by_month)) {
                                        $other_month = null;

                                        switch ($instrument) {
                                            case Base::PAIR_AUD:
                                                $other_month = new Aud($option_by_month->_expiration, $pdf_files_date);

                                                break;

                                            case Base::PAIR_CAD:
                                                $other_month = new Cad($option_by_month->_expiration, $pdf_files_date);

                                                break;

                                            case Base::PAIR_CHF:
                                                $other_month = new Chf($option_by_month->_expiration, $pdf_files_date);

                                                break;

                                            case Base::PAIR_EUR:
                                                $other_month = new Eur($option_by_month->_expiration, $pdf_files_date);

                                                break;

                                            case Base::PAIR_GBP:
                                                $other_month = new Gbp($option_by_month->_expiration, $pdf_files_date);

                                                break;

                                            case Base::PAIR_JPY:
                                                $other_month = new Jpy($option_by_month->_expiration, $pdf_files_date);

                                                break;

                                            case Base::PAIR_XAU:
                                                $other_month = new Xau($option_by_month->_expiration, $pdf_files_date);

                                                break;
                                        }

                                        if ($other_month) {
                                            if ($option->_option_month != $option_by_month->_option_month) {
                                                $other_month->update_day_table = false;
                                                $other_month->update_fractal_field_table = false;
                                            }

                                            $other_month->parse(false);

                                            unset($option_by_month);
                                            unset($other_month);
                                        }
                                    }
                                }
                            }
                        }

                        break;

                    case Base::PARSER_TYPE_JSON:
                        if ($pair_obj && ($option = $pair_obj->getOption())) {
                            $content = @file_get_contents($pair_obj->cme_file_path . env('CME_JSON_FILE_NAME'));

                            if (!empty($content)) {
                                $content = json_decode($content, true);

                                if (count($content) !== 0) {
                                    foreach ($content as $month => $month_data) {
                                        $option_by_month = $pair_obj->getOptionDataByMonth($month);

                                        if (!empty($option_by_month)) {
                                            $other_month = null;

                                            switch ($instrument) {
                                                case Base::PAIR_AUD:
                                                    $other_month = new Aud($option_by_month->_expiration, $pdf_files_date);

                                                    break;

                                                case Base::PAIR_CAD:
                                                    $other_month = new Cad($option_by_month->_expiration, $pdf_files_date);

                                                    break;

                                                case Base::PAIR_CHF:
                                                    $other_month = new Chf($option_by_month->_expiration, $pdf_files_date);

                                                    break;

                                                case Base::PAIR_EUR:
                                                    $other_month = new Eur($option_by_month->_expiration, $pdf_files_date);

                                                    break;

                                                case Base::PAIR_GBP:
                                                    $other_month = new Gbp($option_by_month->_expiration, $pdf_files_date);

                                                    break;

                                                case Base::PAIR_JPY:
                                                    $other_month = new Jpy($option_by_month->_expiration, $pdf_files_date);

                                                    break;

                                                case Base::PAIR_XAU:
                                                    $other_month = new Xau($option_by_month->_expiration, $pdf_files_date);

                                                    break;
                                            }

                                            if ($other_month) {
                                                if ($option->_option_month != $option_by_month->_option_month) {
                                                    $other_month->update_day_table = false;
                                                    $other_month->update_fractal_field_table = false;
                                                }

                                                if (!empty($month_data[Base::CME_BULLETIN_TYPE_CALL]) && !empty($month_data[Base::CME_BULLETIN_TYPE_PUT])) {
                                                    $other_month->parse(false, array_values($month_data[Base::CME_BULLETIN_TYPE_CALL]), array_values($month_data[Base::CME_BULLETIN_TYPE_PUT]));
                                                }

                                                unset($option_by_month);
                                                unset($other_month);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        break;
                }

                echo ' Закончили.' . "\n";

                $pdf_files_date_from = strtotime('+1 DAY', $pdf_files_date_from);
            }
        }
    }
}
