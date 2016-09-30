<?php

namespace App\Console\Commands;

use App\Services\Cme\Base;
use App\Services\Cme\Jpy;
use Illuminate\Console\Command;

class ParseJpy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseJpy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by JPY pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $jpy = new Jpy();

        switch (env('CME_PARSER_USE')) {
            case Base::PARSER_TYPE_PDF:
                if (($files = $jpy->getFiles()) && ($option = $jpy->getOption())) {
                    $months = $jpy->getMonths($jpy->getCmeFilePath() . $files[$jpy::CME_BULLETIN_TYPE_CALL], $option->_option_month);

                    if (count($months) !== 0) {
                        foreach ($months as $month) {
                            $option_by_month = $jpy->getOptionDataByMonth($month);

                            if (!empty($option_by_month)) {
                                $other_month = new Jpy($option_by_month->_expiration);

                                if ($option->_option_month != $option_by_month->_option_month) {
                                    $other_month->update_day_table = false;
                                    $other_month->update_fractal_field_table = false;
                                }

                                $other_month->parse();

                                unset($option_by_month);
                                unset($other_month);
                            }
                        }
                    }
                }

                break;

            case Base::PARSER_TYPE_JSON:
                $option = $jpy->getOption();
                $content = @file_get_contents($jpy->cme_file_path . env('CME_JSON_FILE_NAME'));

                if (!empty($content)) {
                    $content = json_decode($content, true);

                    if (count($content) !== 0) {
                        foreach ($content as $month => $month_data) {
                            $option_by_month = $jpy->getOptionDataByMonth($month);

                            if (!empty($option_by_month)) {
                                $other_month = new Jpy($option_by_month->_expiration);

                                if ($option->_option_month != $option_by_month->_option_month) {
                                    $other_month->update_day_table = false;
                                    $other_month->update_fractal_field_table = false;
                                }

                                $other_month->parse(true, array_values($month_data[Base::CME_BULLETIN_TYPE_CALL]), array_values($month_data[Base::CME_BULLETIN_TYPE_PUT]));

                                unset($option_by_month);
                                unset($other_month);
                            }
                        }
                    }
                }

                break;
        }
    }
}
