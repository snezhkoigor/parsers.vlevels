<?php

namespace App\Console\Commands;

use App\Services\Cme\Base;
use Illuminate\Console\Command;
use App\Services\Cme\Eur;

class ParseEur extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseEur';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by EUR pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $eur = new Eur();

        switch (env('CME_PARSER_USE')) {
            case Base::PARSER_TYPE_PDF:
                if (($files = $eur->getFiles()) && ($option = $eur->getOption())) {
                    $months = $eur->getMonths($eur->getCmeFilePath() . $files[$eur::CME_BULLETIN_TYPE_CALL], $option->_option_month);

                    if (count($months) !== 0) {
                        foreach ($months as $month) {
                            $option_by_month = $eur->getOptionDataByMonth($month);

                            if (!empty($option_by_month)) {
                                $other_month = new Eur($option_by_month->_expiration);

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
                $option = $eur->getOption();
                $content = @file_get_contents($eur->cme_file_path . env('CME_JSON_FILE_NAME'));

                if (!empty($content)) {
                    $content = json_decode($content, true);

                    if (count($content) !== 0) {
                        foreach ($content as $month => $month_data) {
                            $option_by_month = $eur->getOptionDataByMonth($month);

                            if (!empty($option_by_month)) {
                                $other_month = new Eur($option_by_month->_expiration);

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
