<?php

namespace App\Console\Commands;

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

        if (($files = $eur->getFiles()) && ($option = $eur->getOption())) {
            $months = $eur->getMonths($eur->getCmeFilePath() . $files[$eur::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $eur->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Eur(strtotime("-1 DAY", $option_by_month->_expiration));

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
